<?php

declare(strict_types=1);

namespace mermshaus\fine;

use GdImage;
use RuntimeException;

final class ImageTools
{
    /**
     * @throws RuntimeException
     */
    public function loadImage(string $imagePath): GdImage
    {
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

        $image = null;

        $extensions = [
            'gif' => ['gif'],
            'jpg' => ['jpg', 'jpeg'],
            'png' => ['png'],
            'webp' => ['webp'],
        ];

        if (in_array($extension, $extensions['jpg'], true)) {
            $image = imagecreatefromjpeg($imagePath);
        } elseif (in_array($extension, $extensions['png'], true)) {
            $image = imagecreatefrompng($imagePath);
        } elseif (in_array($extension, $extensions['gif'], true)) {
            $image = imagecreatefromgif($imagePath);
        } elseif (in_array($extension, $extensions['webp'], true)) {
            $image = imagecreatefromwebp($imagePath);
        }

        if (!$image instanceof GdImage) {
            throw new RuntimeException(sprintf('Couldn\'t open image in "%s"', $imagePath));
        }

        if (in_array($extension, $extensions['jpg'], true)) {
            $image = $this->adjustRotation($imagePath, $image);
        }

        return $image;
    }

    /**
     * Only works for JPEG images (?).
     */
    private function adjustRotation(string $imagePath, GdImage $image): GdImage
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

    public function createThumb(GdImage $image, int $width, int $height): GdImage
    {
        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);

        $boxWidth = $width;
        $boxHeight = $height;

        $sfw = $imageWidth / $boxWidth;
        $sfh = $imageHeight / $boxHeight;

        if ($sfw < $sfh) {
            $tmpBoxWidth = (int) round($boxWidth * $sfw);
            $tmpBoxHeight = (int) round($boxHeight * $sfw);
        } else {
            $tmpBoxWidth = (int) round($boxWidth * $sfh);
            $tmpBoxHeight = (int) round($boxHeight * $sfh);
        }

        $dstim = imagecreatetruecolor($tmpBoxWidth, $tmpBoxHeight);

        if ($sfw < $sfh) {
            imagecopy(
                $dstim,
                $image,
                0,
                0,
                0,
                (int) round(($imageHeight - $tmpBoxHeight) / 2),
                $tmpBoxWidth,
                $tmpBoxHeight,
            );
        } else {
            imagecopy(
                $dstim,
                $image,
                0,
                0,
                (int) round(($imageWidth - $tmpBoxWidth) / 2),
                0,
                $tmpBoxWidth,
                $tmpBoxHeight,
            );
        }

        $dstim2 = imagecreatetruecolor($boxWidth, $boxHeight);

        imagecopyresampled($dstim2, $dstim, 0, 0, 0, 0, $boxWidth, $boxHeight, $tmpBoxWidth, $tmpBoxHeight);

        return $dstim2;
    }

    public function scale(GdImage $image, int $maxWidth, int $maxHeight, bool $enlarge = false): GdImage
    {
        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);

        if ($enlarge === false && $imageWidth <= $maxWidth && $imageHeight <= $maxHeight) {
            return $image;
        }

        $f = max($imageWidth / $maxWidth, $imageHeight / $maxHeight);

        $tmpBoxWidth = (int) round($imageWidth / $f);
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
