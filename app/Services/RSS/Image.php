<?php

namespace App\Services\RSS;

use App\Services\Base;

class Image extends Base {

    public function handle($params) {
        $htmlFile = $params["html"];
        $imageFile = $params["image"];

        exec("wkhtmltoimage $htmlFile $imageFile");

        [$image, $imageType] = explode(".", $imageFile);
        $compressedImageFile = $image ."_compressed.". $imageType;

        exec("pngquant --force --output $compressedImageFile $imageFile");
    }
}