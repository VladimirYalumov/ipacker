@extends('layouts.app')

@section('content')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>IMAGE-PACKER</title>
    </head>
    <body>
        <div class="container">
            <div class="row">
                <div class="col-9">
                    <div class="card">
                        <div class="card-header">Picture Packer</div>
                        <div class="card-body">
                            <form action="{{route('pack_images_pictures')}}" method="post" enctype="multipart/form-data">
                                @csrf

                                <div class="form-group row">
                                    <label for="files" class="col-4 col-form-label text-md-right">Files</label>

                                    <div class="col-6">
                                        <div class="custom-file">
                                            <input id = "files" type="file" class="custom-file-input" name="uploadfile[]" multiple="multiple">                                            <label class="custom-file-label" for="customFile">Choose file</label>
                                        </div>

                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="width" class="col-4 col-form-label text-md-right">Canvas width in centimeters</label>

                                    <div class="col-6">
                                        <input id="width" type="number" class="form-control" name="width" value="50">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="dpi" class="col-4 col-form-label text-md-right">DPI</label>
                                    <div class="col-6">
                                        <input id="dpi" type="number" class="form-control" name="dpi" value="150">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-6 offset-4">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="color" id="inlineRadio1" value="CMYK">
                                            <label class="form-check-label" for="inlineRadio1">CMYK</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="color" id="inlineRadio2" value="RGB">
                                            <label class="form-check-label" for="inlineRadio2">RGB</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row mb-0">
                                    <div class="col-4 offset-4">
                                        <button type="submit" class="col-12 btn btn-primary">
                                            Puck
                                        </button>
                                    </div>
                                    <div class="col-2">
                                        <select class="form-control" id="format" name="format">
                                            <option value="jpg">JPG</option>
                                        </select>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-3">
                    @if(isset($property))
                        <div class="row justify-content-center mb-3">
                            <div class="card">
                                <div class="card-header">
                                    Image is ready
                                </div>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">Width: {{$property['width']}} см</li>
                                    <li class="list-group-item">Height: {{$property['height']}} см</li>
                                    <li class="list-group-item">Filled area: {{$property['area']}} м<sup>2</sup></li>
                                    <li class="list-group-item">Full area: {{$property['full_area']}} м<sup>2</sup></li>
                                    <li class="list-group-item">Number of files: {{$property['files_num']}}</li>
                                    <a class="btn btn-dark" href="{{$property['path']}}">Download</a>
                                </ul>
                            </div>
                        </div>
                    @elseif(isset($error))
                        <div class="card border-danger mb-3" style="max-width: 18rem;">
                            <div class="card-header">ERROR</div>
                            <div class="card-body text-danger">
                                <p class="card-text">{{$error}}</p>
                            </div>
                        </div>
                    @endisset
                </div>
            </div>
        </div>
    </body>
</html>
@endsection
