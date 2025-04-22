<?php

\Contao\System::loadLanguageFile('tl_content', 'de');

$strTable = 'tl_files';

\Contao\CoreBundle\DataContainer\PaletteManipulator::create()
    ->addLegend('vision_legend','meta_legend',\Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)
    ->addField('vision_generate','vision_legend',\Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', $strTable);


$GLOBALS['TL_DCA'][$strTable]['fields']['vision_tags'] = [
    'inputType' => 'text',
    'eval' => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA'][$strTable]['fields']['vision_generate'] = [
    'input_field_callback'    => ['tl_files_imagemeta', 'generateButton'],
];



class tl_files_imagemeta extends Contao\Backend {

    /** Return a button to generate AI SEO Content
     * @param \Contao\DataContainer $dc
     * @return string
     */
    public function generateButton(\Contao\DataContainer $dc) {
        $strContent = '
            <script>
                function generateTags(btn,id,mode) {
                    
                    const imagePath = "'.$dc->activeRecord->path.'";
                    const imagemetaField = document.getElementById("ctrl_meta_alt_0");
                    
                    const fetchPromise = fetch("/_imagerecognition?image="+imagePath);
                    
                    btn.disabled = true;

                    console.log("🪄 Lets do some AI Magic 🪄");
                    
                    fetchPromise.then(response => {
                        return response.json();
                    }).then(content => {
                        if(content.success === true) {
                            
                            imagemetaField.value = content.content;
                            // trigger this damn SERP preview
                            imagemetaField.dispatchEvent(new Event("input", { bubbles: true }));
                           
                            btn.disabled = false;
                            console.log("MAGIC 🪄🎩");   
                        } else {
                            btn.disabled = false;
                            alert(content.content);
                        }
                    });
                    
                }
            </script>
            <div class="w50 widget">
                <h3><label for="ctrl_vision_generate">'.$GLOBALS["TL_LANG"]["tl_files"]["vision_generate"][0].'</label></h3>
                <button class="tl_submit" style="margin-right:5px;" onclick="generateTags(this,\'' .$dc->id.'\',\'imagemeta\');return false">'.$GLOBALS["TL_LANG"]["tl_files"]["vision_generate_imagemeta"][0].'</button>
            </div>  
        ';
        return $strContent;
    }
}




