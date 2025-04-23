<?php

$strTable = "tl_imagemeta_config";

$GLOBALS["TL_LANG"][$strTable] = [
    "im_config_legend"       => "Imagemeta settings",
    "im_gpt_token"        => ["OpenAI token","Enter the token here - <a href='https://platform.openai.com/account/api-keys' target='_blank' style='font-weight:bold;'>Generate token here</a>"],
    "im_gpt_model"        => ["OpenAI model","More information about Images and vision <a href='https://platform.openai.com/docs/guides/images?api-mode=responses&lang=javascript&format=url' target='_blank' style='font-weight:bold;'>here</a>."],
    "im_gpt_prompt"       => ["Prompt","Enter an instruction here e.g. Create a short and concise ALT title based on the image"],
    "im_compress_image"       => ["Compress image","To reduce costs, the image is compressed using GD or Imagine."],
];
