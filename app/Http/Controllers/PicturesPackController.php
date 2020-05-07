<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Imagick;

class PicturesPackController extends PackerController
{
    protected function initializePicturesArray() : array {

    $pictureArray = [];

    for($i = 0; $i < count($this->fileNames); $i++){
        $pictureArray[$i]['image'] = new Imagick(self::PATH.$this->fileNames[$i]);
        $pictureArray[$i]['width'] = $pictureArray[$i]['image']->getImageWidth();
        if($pictureArray[$i]['width'] > $this->width){
            $this->error = "Width one of images is bigger then width of main image";
            return [];
        }
        $pictureArray[$i]['height'] = $pictureArray[$i]['image']->getImageHeight();
        $pictureArray[$i]['image']->transformImageColorspace(Imagick::COLORSPACE_CMYK);
        $this->property['area'] += ($pictureArray[$i]['width'] / 0.3937 / $this->dpi) * ($pictureArray[$i]['height'] / 0.3937 / $this->dpi);

        if ($pictureArray[$i]['width'] < $pictureArray[$i]['height']){
            $pictureArray[$i]['image']->rotateImage('white', 90.0);
            $temp = $pictureArray[$i]['width'];
            $pictureArray[$i]['width'] = $pictureArray[$i]['height'];
            $pictureArray[$i]['height'] = $temp;
        }
    }
    return $this->sortPictureArray($pictureArray);
    }
    protected function puck(){

        $pictureArray = $this->initializePicturesArray();

        if(empty($pictureArray)){
            return false;
        }

        $this->levelsArray[0]['startY'] = 0;
        $this->levelsArray[0]['emptyX'] = $this->width;
        $this->levelsArray[0]['height'] = $pictureArray[0]['height'];

        $findFlag = false;
        $horizontalPlanes = [];

        // Ищем куда впихнуть картинки
        for($i = 0; $i < count($pictureArray); $i++){
            // Флаг показывающий нашли мы место на текущих уровнях
            $findFlag = false;

            if($horizontalPlanes != NULL) {
                $underLevelIndex = 0;
                foreach ($horizontalPlanes as $key => $horizontalPlane) {
                    if ($pictureArray[$i]['width'] <= $pictureArray[$i]['height'] &&
                        $pictureArray[$i]['width'] <= $horizontalPlane['height'] &&
                        $pictureArray[$i]['height'] <= $horizontalPlane['width']
                    ) {
                        $pictureArray[$i]['image']->rotateImage('white', 90.0);
                        $temp = $pictureArray[$i]['width'];
                        $pictureArray[$i]['width'] = $pictureArray[$i]['height'];
                        $pictureArray[$i]['height'] = $temp;

                        $this->imageBackGround->compositeImage($pictureArray[$i]['image'],
                            $pictureArray[$i]['image']->getImageCompose(),
                            $horizontalPlane['startX'],
                            $horizontalPlane['startY'] - $pictureArray[$i]['height']);

                        $underLevelIndex = $key;
                        $findFlag = true; // Нашли)
                        break;
                    }
                    elseif ($pictureArray[$i]['width'] > $pictureArray[$i]['height'] &&
                        $pictureArray[$i]['height'] <= $horizontalPlane['height'] &&
                        $pictureArray[$i]['width'] <= $horizontalPlane['width']
                    ) {
                        $this->imageBackGround->compositeImage($pictureArray[$i]['image'],
                            $pictureArray[$i]['image']->getImageCompose(),
                            $horizontalPlane['startX'],
                            $horizontalPlane['startY'] - $pictureArray[$i]['height']);

                        $underLevelIndex = $key;
                        $findFlag = true; // Нашли)
                        break;
                    }
                }
                if ($findFlag){
                    $horizontalPlanes[$underLevelIndex]['height'] = $horizontalPlanes[$underLevelIndex]['height'] - $pictureArray[$i]['height'];
                    $horizontalPlanes[$underLevelIndex]['startY'] = $horizontalPlanes[$underLevelIndex]['startY'] - $pictureArray[$i]['height'];
                    continue;
                }
            }

            // Проходим по всем уровням
            for($j = 0; $j < count($this->levelsArray); $j++) {
                // Если в уровне j помещается картинка по ширине
                if ($pictureArray[$i]['width'] <= $this->levelsArray[$j]['emptyX']) {
                    // Вставляем
                    $this->imageBackGround->compositeImage($pictureArray[$i]['image'],
                        $pictureArray[$i]['image']->getImageCompose(),
                        $this->width-$this->levelsArray[$j]['emptyX'],
                        $this->height - $pictureArray[$i]['height'] - $this->levelsArray[$j]['startY']);
                    $findFlag = true; // Нашли)
                    if($i != 0) {
                        $horizontalPlanes[]['startX'] = $this->width - $this->levelsArray[$j]['emptyX'];
                        $horizontalPlanes[count($horizontalPlanes) - 1]['startY'] = $this->height - $pictureArray[$i]['height'] - $this->levelsArray[$j]['startY'];
                        $horizontalPlanes[count($horizontalPlanes) - 1]['height'] = $this->levelsArray[$j]['height'] - $pictureArray[$i]['height'] - $this->levelsArray[$j]['startY'];
                        $horizontalPlanes[count($horizontalPlanes) - 1]['width'] = $pictureArray[$i]['width'];
                    }
                    $this->levelsArray[$j]['emptyX'] -= $pictureArray[$i]['width']; // Уменьшили пустое пространство (Стартовую точку для следующей картинки)
                    break;
                }
                if(!$findFlag){
                    $pictureArray[$i]['image']->rotateImage('white', -90.0);
                    $temp = $pictureArray[$i]['width'];
                    $pictureArray[$i]['width'] = $pictureArray[$i]['height'];
                    $pictureArray[$i]['height'] = $temp;
                    $rotateFlag = true;
                }
            }

            // Если на предыдущих уровнях не нащли места(
            if($findFlag == false){
                $lastElementNumber = count($this->levelsArray)-1;
                $this->levelsArray[] = [
                    'startY' => $this->levelsArray[$lastElementNumber]['height'],
                    'emptyX' => $this->width,
                    'height' => $this->levelsArray[$lastElementNumber]['height'] + $pictureArray[$i]['height']
                ];

                $this->imageBackGround->compositeImage($pictureArray[$i]['image'],
                    $pictureArray[$i]['image']->getImageCompose(),
                    $this->width - $this->levelsArray[$lastElementNumber+1]['emptyX'],
                    $this->height - $pictureArray[$i]['height'] - $this->levelsArray[$lastElementNumber+1]['startY']);
                $this->levelsArray[$lastElementNumber+1]['emptyX'] -= $pictureArray[$i]['width']; // Уменьшили пустое пространство (Стартовую точку для следующей картинки)
            }
        }
        return true;
    }
}