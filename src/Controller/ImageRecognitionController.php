<?php

namespace Codebuster\ContaoOpenaiImagemetaBundle\Controller;

use Contao\Config;
use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\Image\ResizeConfiguration;
use Contao\System;
use Contao\Input;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/_imagerecognition', name: ImageRecognitionController::class, defaults: ['_scope' => 'backend', '_token_check' => true])]
class ImageRecognitionController
{
    public function __construct(private readonly ImageFactoryInterface $imageFactory)
    {
    }
    public function __invoke(Request $request): Response
    {
        $container = System::getContainer();
        $blnBackend = $container->get('contao.security.token_checker')->hasBackendUser();
        $base = System::getContainer()->getParameter('kernel.project_dir');
        $strImagepath = "";
        if ($blnBackend === false) {
            return new Response('Bad Request', Response::HTTP_BAD_REQUEST);
        }

        if(Input::get('image')) {
            $base64image = $this->resizeImageToBase64($base.'/'.Input::get('image'));
        }

        $response = $this->doRequest($base64image);

        return new Response($response,Response::HTTP_OK);
    }

    private function doRequest(string $base64image): string {

        $strReturn = "";
        $token = Config::get('gpt_token');
        $prompt = Config::get('gpt_prompt');
        $model = Config::get('gpt_model');

        if($token & $base64image) {
            $endpoint = "https://api.openai.com/v1/responses";
            $body = '{
              "model": "gpt-4.1-nano",
              "input": [
                {
                  "role": "user",
                  "content": [
                    {
                      "type": "input_text",
                      "text": "Erstelle einen kurzen und prägnanten ALT-Titel basierend auf dem Bild. Keine Schlüsselwörter, nur Sätze Sei spezifisch und beschreibend. Beginne den ALT-Titel nicht mit „Bild von …“ oder „Foto von …“, da dies redundant ist. Bitte erstelle einen beschreibenden ALT-Titel"
                    },
                    {
                      "type": "input_image",
                      "image_url": "'.$base64image.'"
                    }
                  ]
                }
              ]
            }';

            $client = new Client();
            $headers = [
                "Content-Type" => "application/json",
                "Authorization" => "Bearer ".$token,
            ];
            $request = new \GuzzleHttp\Psr7\Request('POST', $endpoint, $headers,$body);
            $res = $client->sendAsync($request)->wait();

            $content = json_decode($res->getBody()->getContents());

            if(isset($content->status) && $content->status == "completed") {
                $output = $content->output[0]->content[0]->text;
                $arrReturn = [
                    "content" => $output,
                    "input_tokens" => $content->usage->input_tokens,
                    "output_tokens" => $content->usage->output_tokens,
                    "total_tokens" => $content->usage->total_tokens,
                    "success" => true
                ];
            } else {
                $arrReturn = [
                    "content" => 'no content',
                    "success" => false
                ];
            }

            $strReturn = json_encode($arrReturn);
        }

        header('Content-Type: application/json');
        return $strReturn;
    }

    private function resizeImageToBase64(string $sourcePath, int $maxLength = 1024): ?string
    {
        // Versuche zuerst Imagine
        if (class_exists(\Imagine\Gd\Imagine::class)) {
            return $this->resizeWithImagineBase64($sourcePath, $maxLength);
        }

        // Fallback auf GD
        if (function_exists('gd_info')) {
            return $this->resizeWithGdBase64($sourcePath, $maxLength);
        }

        return null; // Kein Backend verfügbar
    }

    private function resizeWithImagineBase64(string $sourcePath, int $maxLength): ?string
    {
        try {
            $imagine = new \Imagine\Gd\Imagine(); // oder Imagick, wenn du willst
            $image = $imagine->open($sourcePath);

            $size = $image->getSize();
            $width = $size->getWidth();
            $height = $size->getHeight();
            $ratio = $width / $height;

            if ($width > $height) {
                $newWidth = $maxLength;
                $newHeight = intval($maxLength / $ratio);
            } else {
                $newHeight = $maxLength;
                $newWidth = intval($maxLength * $ratio);
            }

            $box = new \Imagine\Image\Box($newWidth, $newHeight);
            $tmp = tmpfile();
            $tmpPath = stream_get_meta_data($tmp)['uri'];

            $image->resize($box)->save($tmpPath, ['quality' => 85]);

            $data = file_get_contents($tmpPath);
            fclose($tmp);

            $mime = mime_content_type($sourcePath);
            return 'data:' . $mime . ';base64,' . base64_encode($data);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function resizeWithGdBase64(string $sourcePath, int $maxLength): ?string
    {
        [$width, $height, $type] = getimagesize($sourcePath);
        $ratio = $width / $height;

        if ($width > $height) {
            $newWidth = $maxLength;
            $newHeight = intval($maxLength / $ratio);
        } else {
            $newHeight = $maxLength;
            $newWidth = intval($maxLength * $ratio);
        }

        switch ($type) {
            case IMAGETYPE_JPEG: $src = imagecreatefromjpeg($sourcePath); break;
            case IMAGETYPE_PNG:  $src = imagecreatefrompng($sourcePath);  break;
            case IMAGETYPE_GIF:  $src = imagecreatefromgif($sourcePath);  break;
            default: return null;
        }

        $dst = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        switch ($type) {
            case IMAGETYPE_JPEG: imagejpeg($dst, null, 85); $mime = 'image/jpeg'; break;
            case IMAGETYPE_PNG:  imagepng($dst); $mime = 'image/png'; break;
            case IMAGETYPE_GIF:  imagegif($dst); $mime = 'image/gif'; break;
            default: ob_end_clean(); return null;
        }
        $data = ob_get_clean();

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

}