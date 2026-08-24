<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php";

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

global $APPLICATION, $USER;

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm("Доступ запрещен");
}

Loader::includeModule("iblock");
Loader::includeModule("bnk.propimporter");

$moduleId = "bnk.propimporter";

$errors = [];
$success = [];

$sourceIblockId = (int)Option::get($moduleId, "source_iblock_id", 0);
$targetIblockId = (int)Option::get($moduleId, "target_iblock_id", 0);

$isSelectionSaved = false;
$props = [];

if ($_SERVER["REQUEST_METHOD"] === "POST" && check_bitrix_sessid()) {
    $action = (string)($_POST["action"] ?? "");

    if ($action === "save_iblocks") {
        $sourceIblockId = (int)($_POST["source_iblock_id"] ?? 0);
        $targetIblockId = (int)($_POST["target_iblock_id"] ?? 0);

        if ($sourceIblockId <= 0) {
            $errors[] = "Не выбран исходный инфоблок.";
        }

        if ($targetIblockId <= 0) {
            $errors[] = "Не выбран целевой инфоблок.";
        }

        if ($sourceIblockId > 0 && $targetIblockId > 0 && $sourceIblockId === $targetIblockId) {
            $errors[] = "Исходный и целевой инфоблок не должны совпадать.";
        }

        if (empty($errors)) {
            Option::set($moduleId, "source_iblock_id", $sourceIblockId);
            Option::set($moduleId, "target_iblock_id", $targetIblockId);
            $success[] = "Инфоблоки сохранены. Ниже отображены свойства для импорта.";
            $isSelectionSaved = true;
        }
    }

    if ($action === "copy_props") {
        $sourceIblockId = (int)Option::get($moduleId, "source_iblock_id", 0);
        $targetIblockId = (int)Option::get($moduleId, "target_iblock_id", 0);

        $selectedProps = $_POST["props"] ?? [];

        if ($sourceIblockId <= 0 || $targetIblockId <= 0) {
            $errors[] = "Сначала сохраните исходный и целевой инфоблок.";
        } elseif (!is_array($selectedProps) || empty($selectedProps)) {
            $errors[] = "Не выбрано ни одного свойства.";
        } else {
            $rsProps = CIBlockProperty::GetList(
                ["SORT" => "ASC", "NAME" => "ASC"],
                ["IBLOCK_ID" => $sourceIblockId]
            );

            while ($prop = $rsProps->Fetch()) {
                if (!in_array($prop["ID"], $selectedProps)) {
                    continue;
                }

                if (!empty($prop["CODE"])) {
                    $check = CIBlockProperty::GetList([], [
                        "IBLOCK_ID" => $targetIblockId,
                        "CODE" => $prop["CODE"]
                    ]);

                    if ($check->Fetch()) {
                        $errors[] = "Свойство '{$prop["NAME"]}' уже существует в целевом инфоблоке.";
                        continue;
                    }
                }

                $fields = [
                    "IBLOCK_ID" => $targetIblockId,
                    "NAME" => $prop["NAME"],
                    "ACTIVE" => $prop["ACTIVE"],
                    "SORT" => $prop["SORT"],
                    "CODE" => $prop["CODE"],
                    "DEFAULT_VALUE" => $prop["DEFAULT_VALUE"],
                    "PROPERTY_TYPE" => $prop["PROPERTY_TYPE"],
                    "ROW_COUNT" => $prop["ROW_COUNT"],
                    "COL_COUNT" => $prop["COL_COUNT"],
                    "LIST_TYPE" => $prop["LIST_TYPE"],
                    "MULTIPLE" => $prop["MULTIPLE"],
                    "XML_ID" => $prop["XML_ID"],
                    "FILE_TYPE" => $prop["FILE_TYPE"],
                    "MULTIPLE_CNT" => $prop["MULTIPLE_CNT"],
                    "TMP_ID" => $prop["TMP_ID"],
                    "LINK_IBLOCK_ID" => $prop["LINK_IBLOCK_ID"],
                    "WITH_DESCRIPTION" => $prop["WITH_DESCRIPTION"],
                    "SEARCHABLE" => $prop["SEARCHABLE"],
                    "FILTRABLE" => $prop["FILTRABLE"],
                    "IS_REQUIRED" => $prop["IS_REQUIRED"],
                    "VERSION" => $prop["VERSION"],
                    "USER_TYPE" => $prop["USER_TYPE"],
                    "USER_TYPE_SETTINGS" => $prop["USER_TYPE_SETTINGS"],
                    "HINT" => $prop["HINT"],
                ];

                $ibp = new CIBlockProperty();
                $newPropId = $ibp->Add($fields);

                if ($newPropId) {
                    $success[] = "Свойство '{$prop["NAME"]}' успешно скопировано.";

                    if ($prop["PROPERTY_TYPE"] === "L") {
                        $rsEnum = CIBlockPropertyEnum::GetList(
                            ["SORT" => "ASC", "ID" => "ASC"],
                            ["PROPERTY_ID" => $prop["ID"]]
                        );

                        while ($enum = $rsEnum->Fetch()) {
                            CIBlockPropertyEnum::Add([
                                "PROPERTY_ID" => $newPropId,
                                "VALUE" => $enum["VALUE"],
                                "DEF" => $enum["DEF"],
                                "SORT" => $enum["SORT"],
                                "XML_ID" => $enum["XML_ID"],
                            ]);
                        }
                    }
                } else {
                    $errors[] = "Ошибка копирования '{$prop["NAME"]}': " . $ibp->LAST_ERROR;
                }
            }
        }
    }
}

if ($sourceIblockId > 0) {
    $rsProps = CIBlockProperty::GetList(
        ["SORT" => "ASC", "NAME" => "ASC"],
        ["IBLOCK_ID" => $sourceIblockId]
    );

    while ($prop = $rsProps->Fetch()) {
        $props[] = $prop;
    }
}

$iblocks = [];
$rsIblocks = CIBlock::GetList(
    ["NAME" => "ASC"],
    ["CHECK_PERMISSIONS" => "Y", "MIN_PERMISSION" => "R"]
);

while ($iblock = $rsIblocks->Fetch()) {
    $iblocks[] = $iblock;
}

require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php";

if (!empty($errors)) {
    echo '<div class="adm-info-message-wrap adm-info-message-red"><div class="adm-info-message">';
    echo implode("<br>", $errors);
    echo '</div></div>';
}

if (!empty($success)) {
    echo '<div class="adm-info-message-wrap"><div class="adm-info-message">';
    echo implode("<br>", $success);
    echo '</div></div>';
}
?>

    <div class="adm-detail-block" style="max-width: 1400px; margin: 0 auto;">
        <div class="adm-detail-content-wrap">
            <div class="adm-detail-content">
                <div class="adm-detail-title">Импорт свойств инфоблока</div>

                <div class="adm-info-message" style="margin-bottom: 18px;">
                    Для начала Вам необходимо выбрать инфоблок. Свойства отобразятся после сохранения.
                </div>

                <form method="POST">
                    <?= bitrix_sessid_post(); ?>
                    <input type="hidden" name="action" value="save_iblocks">

                    <table class="adm-detail-content-table edit-table">
                        <tr>
                            <td width="40%">Откуда забрать:</td>
                            <td width="60%">
                                <select name="source_iblock_id" class="adm-detail-iblock-select">
                                    <option value="0">-- Выберите инфоблок --</option>
                                    <?php foreach ($iblocks as $iblock): ?>
                                        <option value="<?= (int)$iblock["ID"] ?>" <?= $sourceIblockId === (int)$iblock["ID"] ? 'selected' : '' ?>>
                                            [<?= (int)$iblock["ID"] ?>] <?= htmlspecialcharsbx($iblock["NAME"]) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Куда переносим:</td>
                            <td>
                                <select name="target_iblock_id" class="adm-detail-iblock-select">
                                    <option value="0">-- Выберите инфоблок --</option>
                                    <?php foreach ($iblocks as $iblock): ?>
                                        <option value="<?= (int)$iblock["ID"] ?>" <?= $targetIblockId === (int)$iblock["ID"] ? 'selected' : '' ?>>
                                            [<?= (int)$iblock["ID"] ?>] <?= htmlspecialcharsbx($iblock["NAME"]) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <div class="adm-detail-content-btns">
                        <input type="submit" class="adm-btn-save" value="Сохранить">
                    </div>
                </form>

                <?php if ($sourceIblockId > 0 && $targetIblockId > 0): ?>
                    <hr style="margin: 25px 0;">

                    <form method="POST">
                        <?= bitrix_sessid_post(); ?>
                        <input type="hidden" name="action" value="copy_props">

                        <div style="margin-bottom: 12px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                            <label style="display: inline-flex; align-items: center; gap: 6px;">
                                <input type="checkbox" id="select_all_props">
                                <span>Выбрать все</span>
                            </label>

                            <input
                                type="text"
                                id="prop_filter"
                                class="adm-input"
                                placeholder="Фильтр по названию или коду свойства"
                                style="min-width: 320px; max-width: 420px; width: 100%;"
                            >
                        </div>

                        <div class="adm-list-table-wrap">
                            <table class="adm-list-table" style="width:100%;">
                                <thead>
                                <tr class="adm-list-table-header">
                                    <td style="width:50px; text-align:center;">#</td>
                                    <td>Название</td>
                                    <td style="width:220px;">Код</td>
                                    <td style="width:180px;">Тип</td>
                                    <td style="width:120px; text-align:center;">Множественное</td>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (!empty($props)): ?>
                                    <?php foreach ($props as $prop): ?>
                                        <tr class="adm-list-table-row js-prop-row"
                                            data-search="<?= htmlspecialcharsbx(mb_strtolower($prop['NAME'] . ' ' . $prop['CODE'])) ?>">
                                            <td align="center">
                                                <input type="checkbox"
                                                       name="props[]"
                                                       value="<?= htmlspecialcharsbx($prop['ID']) ?>"
                                                       class="js-prop-checkbox">
                                            </td>
                                            <td>
                                                <div style="font-weight: 600;"><?= htmlspecialcharsbx($prop["NAME"]) ?></div>
                                                <div style="color: #8d9298; font-size: 12px; margin-top: 2px;">
                                                    ID: <?= (int)$prop["ID"] ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($prop["CODE"])): ?>
                                                    <code><?= htmlspecialcharsbx($prop["CODE"]) ?></code>
                                                <?php else: ?>
                                                    <span style="color:#a0a5ab;">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialcharsbx($prop["PROPERTY_TYPE"]) ?>
                                                <?= $prop["USER_TYPE"] ? ' (' . htmlspecialcharsbx($prop["USER_TYPE"]) . ')' : '' ?>
                                            </td>
                                            <td align="center"><?= $prop["MULTIPLE"] === "Y" ? "Да" : "Нет" ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr class="adm-list-table-row">
                                        <td colspan="5" style="text-align:center; padding: 20px; color: #8d9298;">
                                            В выбранном инфоблоке свойства не найдены.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="adm-detail-content-btns" style="margin-top: 18px;">
                            <input type="submit" class="adm-btn-save" value="Скопировать выбранные свойства">
                            <input type="button" class="adm-btn" value="Снять выделение" id="unselect_all_props">
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        BX.ready(function () {
            var selectAll = document.getElementById('select_all_props');
            var unselectAll = document.getElementById('unselect_all_props');
            var filterInput = document.getElementById('prop_filter');
            var checkboxes = document.querySelectorAll('.js-prop-checkbox');
            var rows = document.querySelectorAll('.js-prop-row');

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach(function (checkbox) {
                        if (checkbox.closest('tr').style.display !== 'none') {
                            checkbox.checked = selectAll.checked;
                        }
                    });
                });
            }

            if (unselectAll) {
                unselectAll.addEventListener('click', function () {
                    checkboxes.forEach(function (checkbox) {
                        checkbox.checked = false;
                    });

                    if (selectAll) {
                        selectAll.checked = false;
                    }
                });
            }

            if (filterInput) {
                filterInput.addEventListener('input', function () {
                    var query = this.value.toLowerCase().trim();

                    rows.forEach(function (row) {
                        var haystack = row.getAttribute('data-search') || '';
                        row.style.display = (query === '' || haystack.indexOf(query) !== -1) ? '' : 'none';
                    });
                });
            }
        });
    </script>

<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php";