<?php

$strTable = "tl_imagemeta_config";

$GLOBALS["TL_LANG"][$strTable] = [
    "im_config_legend"       => "Imagemeta Einstellungen",
    "im_gpt_token"        => ["OpenAI Token","Tragen Sie hier den Token ein - <a href='https://platform.openai.com/account/api-keys' target='_blank' style='font-weight:bold;'>Hier Token generieren</a>"],
    "im_gpt_model"        => ["OpenAI Model","Weitere Infos zu Images und vision <a href='https://platform.openai.com/docs/guides/images?api-mode=responses&lang=javascript&format=url' target='_blank' style='font-weight:bold;'>hier</a>."],
    "im_gpt_prompt"       => ["Prompt","Tragen Sie hier eine Anweisung ein z.b: Erstelle einen kurzen und prägnanten ALT-Titel basierend auf dem Bild"],
    "im_compress_image"       => ["Bild komprimieren","Um kosten zu reduzieren, wird das Bild mit Hilfe von GD oder Imagine komprimiert."],
];
