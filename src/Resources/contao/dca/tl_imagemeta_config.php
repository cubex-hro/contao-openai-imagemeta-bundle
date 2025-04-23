<?php

/**
 * File tl_imagemeta_config
 */

use Codebuster\ContaoOpenaiImagemetaBundle\Models\ContentElementsModel;
use Contao\Database;
use Contao\DC_File;

$strTable = 'tl_imagemeta_config';

$GLOBALS['TL_DCA'][$strTable] = [
//Config
    'config' => [
        'dataContainer' => DC_File::class
    ],
    //Palettes
    'palettes' => [
        'default' => '
            {im_config_legend},im_gpt_token,im_gpt_model,im_gpt_prompt,im_compress_image;
        '
    ],
    //Fields
    'fields' => [
        'im_gpt_token' => [
            'label' => &$GLOBALS['TL_LANG'][$strTable]['im_gpt_token'],
            'inputType' => 'text',
            'eval' => ['tl_class' => 'clr w50','hideInput' => true]
        ],
        'im_gpt_model' => [
            'label' => &$GLOBALS['TL_LANG'][$strTable]['im_gpt_model'],
            'inputType' => 'select',
            'options' => ['gpt-4.1-mini','gpt-4.1-nano'],
            'eval' => ['multiple' => false, 'tl_class' => 'w50']
        ],
        'im_gpt_prompt' => [
            'label' => &$GLOBALS['TL_LANG'][$strTable]['img_gpt_prompt'],
            'inputType' => 'textarea',
            'eval' => ['decodeEntities' => false,'allowHtml' => true, 'preserveTags' => true, 'tl_class' => 'clr']
        ],
        'im_compress_image' => [
            'label' => &$GLOBALS['TL_LANG'][$strTable]['im_compress_image'],
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'clr'],
        ]
    ]
];