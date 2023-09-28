<?

$arResult = CAllcorp3::getChilds2($arResult);
global $arTheme;
$MENU_TYPE = $arTheme['MEGA_MENU_TYPE']['VALUE'];

if ($MENU_TYPE == 3) {
    CAllcorp3::replaceMenuChilds($arResult, $arParams);
}



// $arResult = \Sibwest\CatalogMenu::prepareStandartMenu($arResult);

// $test = \Sibwest\CatalogMenu::prepareStandartMenu();
// vdump($test);


/*final menu result start*/

$iblock_id = \Bitrix\Main\Config\Option::get('aspro.allcorp3', 'CATALOG_IBLOCK_ID', CAllcorp3Cache::$arIBlocks[SITE_ID]['aspro_allcorp3_catalog']['aspro_allcorp3_catalog'][0]);
const CATALOG_DIR = 'product';

// Проходим по массиву ссылок и находим совпадения с заданым условием, далее получаем нужные нам символьные коды.
$sectionCodes = [];
foreach ($arResult as $menuLink) {
    $menuLinkPart =  explode('/', $menuLink['LINK']);
    if ($menuLinkPart[1] == CATALOG_DIR) {
        $sectionCodes[] = $menuLinkPart[2];
    }
}

// получаем массив данных содержащий информацию об искомых разделах
if (!empty($sectionCodes)) {
    $selectedRes = CIBlockSection::GetList(
        array(),
        array(
            'IBLOCK_ID' => $iblock_id,
            'CODE' => $sectionCodes
        ),
        false,
        [
            "ID",
            "DEPTH_LEVEL",
            "NAME",
            "SECTION_PAGE_URL",
            "IBLOCK_SECTION_ID",
        ]
    );

    while ($r = $selectedRes->GetNext()) {
        $parents[] = $r;
    }
}

// получаем значение ID нужных разделов
$parentIds = array_column($parents, 'ID');

// получаем дочерние элементы из разделов полученных выше. Формат ['SECTION_ID'] => [[0] => [SECTION_VALUES], [1] => [SECTION_VALUES]...];
$rsSect = CIBlockSection::GetList(array('left_margin' => 'asc'), [
    'SECTION_ID' => $parentIds,
    'DEPTH_LEVEL' => 2
], false, [
    "ID",
    "DEPTH_LEVEL",
    "NAME",
    "SECTION_PAGE_URL",
    "IBLOCK_SECTION_ID",
    "PICTURE"
]);

$arChilds = [];
while ($r = $rsSect->GetNext()) {
    $arChilds[$r['IBLOCK_SECTION_ID']][] = $r;
}

// Проивзодим слияние двух массивов. Массив с данными который содержит массив с родительскими элементами и массив с дочерними элементами [0] => [SECTION_VALUES],  [1] => [SECTION_VALUES], [2] => [SECTION_VALUES]...
$arRes = [];
foreach ($parents as $item) {
    $arRes[] = $item;
    if ($childs = $arChilds[$item['ID']]) {
        $arRes = array_merge($arRes, $childs ?: []);
    }
}
// vdump($arRes);
//формируем ссылки для меню
$aMenuLinksNew = array();
$menuIndex = 0;
$previousDepthLevel = 1;
foreach ($arRes as $arSection) {
    //проверка является ли элемент родителем
    $isParent = false;
    if ($menuIndex > 0) {
        $isParent = $arSection["DEPTH_LEVEL"] > $previousDepthLevel;
    }

    $previousDepthLevel = $arSection["DEPTH_LEVEL"];

    $aMenuLinksNew[] = [
        'TEXT' => $arSection['NAME'],
        'LINK' => $arSection['SECTION_PAGE_URL'],
        'SELECTED' => false,
        'DEPTH_LEVEL' => $arSection['DEPTH_LEVEL'],
        'IS_PARENT' =>  $isParent,
        'PARAMS' => [
            'PICTURE' => $arSection['PICTURE'],
            'WIDE_MENU' => 'Y'
        ]
    ];
}

$aMenuLinksNew = CAllcorp3::getChilds2($aMenuLinksNew);
$aMenuLinks = [];

foreach ($aMenuLinksNew as $item) {
    $aMenuLinks[$item['LINK']] = $item;
}
$finalResult = [];
foreach ($arResult as $arItem) {
    if ($aMenuLinks[$arItem['LINK']]) {
        $finalResult[] = $aMenuLinks[$arItem['LINK']];
    } else {
        $finalResult[] = $arItem;
    }
}

$arResult = $finalResult;
// vdump($aMenuLinksNew);

/*final menu result end*/
