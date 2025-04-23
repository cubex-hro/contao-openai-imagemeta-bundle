<?php

\Contao\System::loadLanguageFile('tl_content', 'de');

$strTable = 'tl_files';

\Contao\CoreBundle\DataContainer\PaletteManipulator::create()
    ->addLegend('imagemeta_legend','meta_legend',\Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)
    ->addField('imagemeta_generate','imagemeta_legend',\Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', $strTable);

$GLOBALS['TL_DCA'][$strTable]['fields']['imagemeta_generate'] = [
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
                function generateMeta(btn,id,mode) {
                    
                    const imagePath = "'.$dc->activeRecord->path.'";
                    const imagemetaField = document.getElementById("ctrl_meta_alt_0");
                    
                    const fetchPromise = fetch("/_imagemeta?image="+imagePath);
                    
                    btn.disabled = true;

                    console.log("🪄 Lets do some AI Magic 🪄");
                    
                    fetchPromise.then(response => {
                        return response.json();
                    }).then(content => {
                        if(content.success === true) {
                            
                            imagemetaField.value = content.content;
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
                <h3><label for="ctrl_vision_generate">'.$GLOBALS["TL_LANG"]["tl_files"]["imagemeta_actions"][0].'</label></h3>
                <button class="tl_submit" style="margin:5px 0;" onclick="generateMeta(this,\'' .$dc->id.'\',\'imagemeta\');return false">'.$GLOBALS["TL_LANG"]["tl_files"]["imagemeta_generate"][0].'</button>
                <p class="tl_help tl_tip">'.$GLOBALS["TL_LANG"]["tl_files"]["imagemeta_notice"][0].'</p>
            </div>  
        ';
        return $strContent;
    }
}




