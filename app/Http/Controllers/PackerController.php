<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Imagick;

class PackerController extends Controller
{
    protected const LOCAL_PATH = 'uploads/';
    protected const PATH = 'W:\domains\image-packer\public/'.self::LOCAL_PATH;
    protected const INPUT_NAME = 'uploadfile';
    protected $fileNames = [];

    protected $height = 65000;
    protected $width;
    protected $imageBackGround;
    protected $format;
    protected $colorSpace;
    protected $levelsArray;
    protected $property = [];
    protected $dpi;
    protected $error;
    protected $path;


    public function __construct(){
        $this->imageBackGround = new Imagick();

        $this->middleware('auth');

        $this->path = base_path().'/public/'.self::LOCAL_PATH;
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function setStartImage(Request $request){

        $this->dpi = $request->input('dpi');
        $this->width = $request->input('width') * 0.3937 * $this->dpi;
        $this->format = $request->input('format');

        $this->imageBackGround->setResolution($this->dpi,$this->dpi);
        $this->imageBackGround->newImage($this->width, $this->height, 'transparent');
        $this->imageBackGround->setImageFormat($this->format);
        $this->colorSpace = $request->input('color');
        $this->property['width'] = $request->input('width');

        if ($this->colorSpace == 'CMYK') {
            $this->imageBackGround->setImageColorspace(Imagick::COLORSPACE_CMYK);
        }

        $this->property['area'] = 0;

        return true;
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function addFilesToDirectory(Request $request){
        if ($request->hasFile(self::INPUT_NAME)) {
            $files = $request->file(self::INPUT_NAME);
            foreach($files as $k => $file)
            if ($file->isValid()) {
                $this->fileNames[$k] = uniqid() . '.' . $this->format;
                $file->move($this->path, $this->fileNames[$k]);
            }
            return true;
        }
        else {
            $this->error = 'There is no files in request';
            return false;
        }
    }

    /**
     * @return array
     * @throws \ImagickException
     */
    protected function initializePicturesArray() : array {

        $pictureArray = [];

        for($i = 0; $i < count($this->fileNames); $i++){
            $pictureArray[$i]['image'] = new Imagick($this->path.$this->fileNames[$i]);
            $pictureArray[$i]['width'] = $pictureArray[$i]['image']->getImageWidth();
            if($pictureArray[$i]['width'] > $this->width){
                $this->error = "Width one of images is bigger then width of main image";
                return [];
            }
            $pictureArray[$i]['height'] = $pictureArray[$i]['image']->getImageHeight();
            $pictureArray[$i]['image']->transformImageColorspace(Imagick::COLORSPACE_CMYK);
            $this->property['area'] += ($pictureArray[$i]['width'] / 0.3937 / $this->dpi) * ($pictureArray[$i]['height'] / 0.3937 / $this->dpi);
        }
        return $this->sortPictureArray($pictureArray);
    }

    protected function sortPictureArray($pictureArray){
        for ($j = 0; $j < count($pictureArray) - 1; $j++){
            for ($i = 0; $i < count($pictureArray) - $j - 1; $i++){
                // если текущий элемент больше следующего
                if ($pictureArray[$i]['height'] < $pictureArray[$i + 1]['height']){
                    // меняем местами элементы
                    $tmp_var = $pictureArray[$i + 1];
                    $pictureArray[$i + 1] = $pictureArray[$i];
                    $pictureArray[$i] = $tmp_var;
                }
            }
        }

        return $pictureArray;

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
                $rotateFlag = false;
                if(
                    ($pictureArray[$i]['width'] > $pictureArray[$i]['height']) &&
                    ($pictureArray[$i]['width'] <= ($this->levelsArray[$j]['height'] - $this->levelsArray[$j]['startY']))
                ) {
                    $pictureArray[$i]['image']->rotateImage('white', 90.0);
                    $temp = $pictureArray[$i]['width'];
                    $pictureArray[$i]['width'] = $pictureArray[$i]['height'];
                    $pictureArray[$i]['height'] = $temp;
                    $rotateFlag = true;
                }
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
                if($rotateFlag && !$findFlag){
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

    public function index(Request $request){

        $this->setStartImage($request);

        if ($this->addFilesToDirectory($request) == false){
            $error = $this->error = 'There is no files in request';
            return view('welcome')->with(compact('error',$error));
        }

        if(!$this->puck()){
            $error = $this->error;
            return view('welcome')->with(compact('error',$error));
        }

        $this->imageBackGround->cropImage(
            $this->width,
            $this->height,
            0,
            $this->height - $this->levelsArray[count($this->levelsArray)-1]['height']
        );

        $this->property['height'] = round($this->imageBackGround->getImageHeight() / 0.3937 / $this->dpi, 2);
        $this->property['width'] = round($this->property['width'], 2);
        $this->property['area'] = round($this->property['area'] * 0.01, 2);
        $this->property['full_area'] = round($this->property['height']*$this->property['width']*0.01, 2);
        $this->imageBackGround->setImageUnits(imagick::RESOLUTION_PIXELSPERINCH);
        $mainImagePath = uniqid() . '.' . $this->format;
        $this->imageBackGround->writeImage($this->path.'/'.$mainImagePath);
        $this->property['path'] = self::LOCAL_PATH.$mainImagePath;
        $this->property['files_num'] = count($this->fileNames);

        for($i = 0; $i < count($this->fileNames); $i++){
            unlink($this->path.$this->fileNames[$i]);
        }

        $property = $this->property;

        return view('welcome')->with(compact('property',$property));
    }
}


