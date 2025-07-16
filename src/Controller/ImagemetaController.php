<?php

namespace Codebuster\ContaoOpenaiImagemetaBundle\Controller;

use Contao\Config;
use Contao\Image\ResizeConfiguration;
use Contao\System;
use Contao\Input;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/_imagemeta", name=ImagemetaController::class, defaults={"_scope" = "frontend", "_token_check" = true})
 */
class ImagemetaController
{

    public function __invoke(Request $request): Response
    {
        $container = System::getContainer();
        $blnBackend = $container->get('contao.security.token_checker')->hasBackendUser();
        $base = System::getContainer()->getParameter('kernel.project_dir');

        if ($blnBackend === false) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Bad Request (no backend user)'
            ], Response::HTTP_BAD_REQUEST);
        }

        $base64image = '';
        if(Input::get('image')) {
            $image = $base.'/'.Input::get('image');
            if(Config::get('im_compress_image')) {
                $base64image = $this->resizeImageToBase64($image);
            } else {
                $type = pathinfo($image, PATHINFO_EXTENSION);
                $data = file_get_contents($image);
                $base64image = 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        }

        if(!$base64image) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Konnte kein Base64 Bild generieren'
            ], Response::HTTP_BAD_REQUEST);
        }

        [$responseData, $statusCode] = $this->doRequest($base64image);

        return $this->jsonResponse($responseData, $statusCode);
    }


    private function doRequest(string $base64image): array
    {
        $token = Config::get('im_gpt_token');
        $prompt = Config::get('im_gpt_prompt') ?: 'Erstelle einen kurzen und prägnanten ALT-Titel basierend auf dem Bild';
        $model = Config::get('im_gpt_model') ?: 'gpt-4.1-mini';

        if (!$token || !$base64image) {
            return [[
                'success' => false,
                'message' => 'Token oder Bilddaten fehlen'
            ], Response::HTTP_BAD_REQUEST];
        }

        $endpoint = "https://api.openai.com/v1/responses";
        $body = json_encode([
            "model" => $model,
            "input" => [[
                "role" => "user",
                "content" => [
                    [ "type" => "input_text", "text" => $prompt ],
                    [ "type" => "input_image", "image_url" => $base64image ]
                ]
            ]]
        ]);

        $client = new Client();
        $headers = [
            "Content-Type" => "application/json",
            "Authorization" => "Bearer ".$token,
        ];

        try {
            $request = new \GuzzleHttp\Psr7\Request('POST', $endpoint, $headers, $body);
            $res = $client->send($request);

            $content = json_decode($res->getBody()->getContents());

            if (isset($content->status) && $content->status === "completed") {
                return [[
                    "success" => true,
                    "content" => $content->output[0]->content[0]->text ?? '',
                    "input_tokens" => $content->usage->input_tokens ?? null,
                    "output_tokens" => $content->usage->output_tokens ?? null,
                    "total_tokens" => $content->usage->total_tokens ?? null
                ], Response::HTTP_OK];
            }

            return [[
                "success" => false,
                "message" => 'API Response unvollständig oder fehlerhaft.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR];

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $status = $e->getResponse()?->getStatusCode() ?? 500;
            $body = json_decode($e->getResponse()?->getBody()?->getContents(), true);

            return [[
                'success' => false,
                'message' => $body['error']['message'] ?? 'Client error',
            ], $status];

        } catch (\Throwable $e) {
            return [[
                'success' => false,
                'message' => 'Fehler bei der Anfrage: '.$e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR];
        }
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

        return null;
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

    private function jsonResponse(array $data, int $status = 200): Response
    {
        return new Response(json_encode($data), $status, [
            'Content-Type' => 'application/json'
        ]);
    }


}