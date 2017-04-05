<?php

namespace mermshaus\fine;

use Exception;

/**
 *
 */
class ImageTools
{
    /**
     *
     * @param string $imagePath
     * @return resource
     * @throws Exception
     */
    public function loadImage($imagePath)
    {
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

        $image = null;

        if (in_array($extension, array('jpg', 'jpeg'))) {
            $image = imagecreatefromjpeg($imagePath);
            $image = $this->adjustRotation($imagePath, $image);
        } elseif (in_array($extension, array('png'))) {
            $image = imagecreatefrompng($imagePath);
        } elseif (in_array($extension, array('gif'))) {
            $image = imagecreatefromgif($imagePath);
        }

        if (!is_resource($image)) {
            throw new Exception(sprintf('Couldn\'t open image in "%s"', $imagePath));
        }

        return $image;
    }

    /**
     *
     *
     * Only works for JPEG images (?)
     *
     * @param string $imagePath
     * @param resource $image
     * @return resource
     */
    private function adjustRotation($imagePath, $image)
    {
        $exif = @exif_read_data($imagePath);

        if (!is_array($exif) || !isset($exif['Orientation'])) {
            return $image;
        }

        switch ($exif['Orientation']) {
            case 3:
                $image = imagerotate($image, 180, 0);
                break;

            case 6:
                $image = imagerotate($image, -90, 0);
                break;

            case 8:
                $image = imagerotate($image, 90, 0);
                break;

            default:
                // nop
        }

        return $image;
    }

    /**
     *
     * @param resource $image
     * @param int $width
     * @param int $height
     * @return resource
     */
    public function createThumb($image, $width, $height)
    {
        $imageWidth  = imagesx($image);
        $imageHeight = imagesy($image);

        $boxWidth  = $width;
        $boxHeight = $height;

        $sfw = $imageWidth / $boxWidth;
        $sfh = $imageHeight / $boxHeight;

        if ($sfw < $sfh) {
            $tmpBoxWidth  = $boxWidth * $sfw;
            $tmpBoxHeight = $boxHeight * $sfw;
        } else {
            $tmpBoxWidth  = $boxWidth * $sfh;
            $tmpBoxHeight = $boxHeight * $sfh;
        }

        $dstim = imagecreatetruecolor($tmpBoxWidth, $tmpBoxHeight);

        if ($sfw < $sfh) {
            imagecopy($dstim, $image, 0, 0, 0, ($imageHeight - $tmpBoxHeight) / 2, $tmpBoxWidth, $tmpBoxHeight);
        } else {
            imagecopy($dstim, $image, 0, 0, ($imageWidth - $tmpBoxWidth) / 2, 0, $tmpBoxWidth, $tmpBoxHeight);
        }

        $dstim2 = imagecreatetruecolor($boxWidth, $boxHeight);

        imagecopyresampled($dstim2, $dstim, 0, 0, 0, 0, $boxWidth, $boxHeight, $tmpBoxWidth, $tmpBoxHeight);

        return $dstim2;
    }

    /**
     *
     * @param resource $image
     * @param int $maxWidth
     * @param int $maxHeight
     * @return resource
     * @throws Exception
     */
    public function scale($image, $maxWidth, $maxHeight, $enlarge = false)
    {
        $imageWidth  = imagesx($image);
        $imageHeight = imagesy($image);

        if ($enlarge === false && $imageWidth <= $maxWidth && $imageHeight <= $maxHeight) {
            return $image;
        }

        $f = max($imageWidth / $maxWidth, $imageHeight / $maxHeight);

        $tmpBoxWidth  = (int) round($imageWidth / $f);
        $tmpBoxHeight = (int) round($imageHeight / $f);

        if ($tmpBoxWidth > $maxWidth) {
            $tmpBoxWidth = $maxWidth;
        }

        if ($tmpBoxHeight > $maxHeight) {
            $tmpBoxHeight = $maxHeight;
        }

        $dstim2 = imagecreatetruecolor($tmpBoxWidth, $tmpBoxHeight);

        imagecopyresampled($dstim2, $image, 0, 0, 0, 0, $tmpBoxWidth, $tmpBoxHeight, $imageWidth, $imageHeight);

        return $dstim2;
    }
}
