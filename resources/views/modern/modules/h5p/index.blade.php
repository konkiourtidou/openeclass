
@extends('layouts.default')

@section('content')

<link rel='stylesheet' href='{{ $urlAppend }}js/bootstrap-select/bootstrap-select.min.css'>
<!-- <script type='text/javascript' src='{{ $urlAppend }}js/bootstrap-select/bootstrap-select.min.js'></script> -->
<script src="{{ $urlAppend }}js/bootstrap-select/bootstrap5-select.min.js"></script>

<script type='text/javascript'>
    $(document).ready(function() {
        $('#createpicker').on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {
            window.location.href = '{{ $urlAppend }}modules/h5p/create.php?course={{ $course_code }}&library=' + $('#createpicker').val();
        });
    });
</script>

<style>
   .dropdown-menu.show{
      max-height:400px;
   }
</style>

<div class="pb-lg-3 pt-lg-3 pb-0 pt-0">

    <div class="container-fluid main-container">

        <div class="row">

            <div id="background-cheat-leftnav" class="col-xl-2 col-lg-3 col-md-0 col-sm-0 col-0 justify-content-center col_sidebar_active"> 
                <div class="d-none d-sm-block d-sm-none d-md-block d-md-none d-lg-block">
                    @include('layouts.partials.sidebar',['is_editor' => $is_editor])
                </div>
            </div>

			<div class="col-xl-10 col-lg-9 col-md-12 col-sm-12 col-12 justify-content-center col_maincontent_active">
                    
                <div class="row p-lg-5 p-md-5 ps-1 pe-2 pt-5 pb-5">

				    <nav class="navbar navbar-expand-lg navrbar_menu_btn">
                        <button type="button" id="menu-btn" class="d-none d-sm-block d-sm-none d-md-block d-md-none d-lg-block btn btn-primary menu_btn_button">
                            <i class="fas fa-align-left"></i>
                            <span></span>
                        </button>
                        
                       
                        <a class="btn btn-primary d-lg-none mr-auto" type="button" data-bs-toggle="offcanvas" href="#collapseTools" role="button" aria-controls="collapseTools" style="margin-top:-10px;">
                            <i class="fas fa-tools"></i>
                        </a>
                    </nav>

                    @include('layouts.common.breadcrumbs', ['breadcrumbs' => $breadcrumbs])

                    <div class="offcanvas offcanvas-start d-lg-none mr-auto" tabindex="-1" id="collapseTools" aria-labelledby="offcanvasExampleLabel">
                        <div class="offcanvas-header">
                            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                        </div>
                        <div class="offcanvas-body">
                            @include('layouts.partials.sidebar',['is_editor' => $is_editor])
                        </div>
                    </div>


					@include('layouts.partials.legend_view',['is_editor' => $is_editor, 'course_code' => $course_code])



					@if(Session::has('message'))
                    <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 mt-5'>
                        <p class="alert {{ Session::get('alert-class', 'alert-info') }} alert-dismissible fade show" role="alert">
                        {!! Session::get('message') !!}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </p>
                    </div>
                    @endif

                    @if($is_editor)

                        
                        <div class='col-12'>
                            <div class='margin-top-thin margin-bottom-fat pull-right'>
                                {{-- Dropdown select for Creating H5P Content --}}
                                @if ($h5pcontenttypes)
                                    <div class='btn-group'>
                                        <select id='createpicker' class='selectpicker' title="{{ trans('langCreate') }}" data-style='btn-primary' data-width='fit'>
                                            
                                            <optgroup label="{{ trans('langH5pInteractiveContent') }}">
                                                <?php $counter = 0; ?>
                                                @foreach ($h5pcontenttypes as $h5pcontenttype)
                                                    @if ($h5pcontenttype->enabled)
                                                        <?php
                                                        $typeTitle = $h5pcontenttype->title;
                                                        $typeVal = $h5pcontenttype->machine_name . " " . $h5pcontenttype->major_version . "." . $h5pcontenttype->minor_version;
                                                        $typeFolder = $h5pcontenttype->machine_name . "-" . $h5pcontenttype->major_version . "." . $h5pcontenttype->minor_version;
                                                        $typeIconPath = $webDir . "/courses/h5p/libraries/" . $typeFolder . "/icon.svg";
                                                        $typeIconUrl = (file_exists($typeIconPath))
                                                            ? $urlAppend . "courses/h5p/libraries/" . $typeFolder . "/icon.svg"  // expected icon
                                                            : $urlAppend . "js/h5p-core/images/h5p_library.svg"; // fallback icon
                                                        $dataContent = "data-content=\"<img src='$typeIconUrl' alt='$typeTitle' width='24px' height='24px'>$typeTitle\"";
                                                        ?>
                                                        @if($counter == 0)
                                                        <option selected>{{ trans('langCreate') }}</option>
                                                        @endif
                                                        <option {!! $dataContent !!}>{{ $typeVal }}</option>
                                                    @endif
                                                    <?php $counter++; ?>
                                                @endforeach
                                            </optgroup>
                                        </select>
                                    </div>
                                @endif
                                <div class='btn-group'>
                                    {{-- Update --}}
                                    <a class='btn btn-success' href='update.php?course={{ $course_code }}' data-placement='bottom' data-toggle='tooltip'  title='{{ trans('langMaj') }}'>
                                        <span class='fa fa-refresh space-after-icon'></span>
                                        <span class='hidden-xs'>{{ trans('langMaj') }}</span>
                                    </a>

                                    {{-- Import --}}
                                    <a class='btn btn-success' href='upload.php?course={{ $course_code }}' data-placement='bottom' data-toggle='tooltip'  title='{{ trans('langImport') }}'>
                                        <span class='fa fa-upload space-after-icon'></span>
                                        <span class='hidden-xs'>{{ trans('langImport') }}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                    @endif

                    @if ($content)
                        <div class='col-12 mt-4'>
                            <div class='table-responsive'>
                                <table class="announcements_table">
                                    <thead>
                                        <tr class="notes_thead">
                                            <th class="text-left text-white">{{ trans('langH5pInteractiveContent') }}</th>
                                            <th class='text-center text-white'>{{ trans('langAttendanceType') }} HP5</th>
                                            <th class="text-center text-white" style="width:109px;">
                                                <span class="fa fa-gears"></span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        @foreach ($content as $item)
                                            <tr>
                                                <td>
                                                    <a href='view.php?course={{ $course_code }}&amp;id={{ $item->id }}'>{{ $item->title }}</a>
                                                </td>
                                                <td class='text-center'>
                                                    <img src='{{$typeIconUrl}}' alt='{{$h5p_content_type_title}}' width='50px' height='50px'> <em>{!! $h5p_content_type_title !!}</em>
                                                </td>
                                                <td class='text-center'>
                                                    @if ($is_editor)
                                                        {!! action_button([
                                                            [ 'icon' => 'fa-edit',
                                                            'title' => trans('langEditChange'),
                                                            'url' => "create.php?course=$course_code&amp;id=$item->id"
                                                            ],
                                                            [ 'icon' => 'fa-times',
                                                            'title' => trans('langDelete'),
                                                            'url' => "delete.php?course=$course_code&amp;id=$item->id",
                                                            'class' => 'delete',
                                                            'confirm' => trans('langConfirmDelete') ]
                                                            ], false) !!}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class='col-12 mt-5'>
                            <div class='alert alert-warning'>
                                {{ trans('langNoH5PContent') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection