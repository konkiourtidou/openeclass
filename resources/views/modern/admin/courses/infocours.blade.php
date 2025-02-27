@extends('layouts.default')

@section('content')

<div class="pb-lg-3 pt-lg-3 pb-0 pt-0">

    <div class="container-fluid main-container">

        <div class="row rowMedium">

            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 justify-content-center col_maincontent_active_Homepage">
                    
                <div class="row p-lg-5 p-md-5 ps-1 pe-1 pt-5 pb-5">

                    @include('layouts.common.breadcrumbs', ['breadcrumbs' => $breadcrumbs])

                    @include('layouts.partials.legend_view',['is_editor' => $is_editor, 'course_code' => $course_code])
    
                    

                    @if(Session::has('message'))
                    <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 mt-5'>
                        <p class="alert {{ Session::get('alert-class', 'alert-info') }} alert-dismissible fade show" role="alert">
                        {!! Session::get('message') !!}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </p>
                    </div>
                    @endif
                    {!! isset($action_bar) ?  $action_bar : '' !!}
                    <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12'>
                        <div class='form-wrapper shadow-sm p-3 rounded'>
                            
                            <form role='form' class='form-horizontal' action='{{ $_SERVER['SCRIPT_NAME'] }}?c={{ $course->code }}' method='post' onsubmit='return validateNodePickerForm();'>
                                <fieldset>
                                    <div class='form-group mt-3'>
                                        <label for='Faculty' class='col-sm-6 control-label-notes'>{{ trans('langFaculty') }}:</label>
                                        <div class='col-sm-12'>
                                            {!! $node_picker !!}
                                        </div>
                                    </div>
                                    <div class='form-group mt-3'>
                                        <label for='fcode' class='col-sm-6 control-label-notes'>{{ trans('langCode') }}</label>
                                        <div class='col-sm-12'>
                                            <input type='text' class='form-control' name='fcode' id='fcode' value='{{ $course->code }}'>
                                        </div>
                                    </div>
                                    <div class='form-group mt-3'>
                                        <label for='title' class='col-sm-6 control-label-notes'>{{ trans('langCourseTitle') }}:</label>
                                        <div class='col-sm-12'>
                                            <input type='text' class='form-control' name='title' id='title' value='{{ $course->title }}'>
                                        </div>
                                    </div>
                                    <div class='form-group mt-3'>
                                        <label for='titulary' class='col-sm-6 control-label-notes'>{{ trans('langTeachers') }}:</label>
                                        <div class='col-sm-12'>
                                            <input type='text' class='form-control' name='titulary' id='titulary' value='{{ $course->prof_name }}'>
                                        </div>
                                    </div>
                                    {!! showSecondFactorChallenge() !!}
                                    {!! generate_csrf_token_form_field() !!}    
                                    <div class='form-group mt-3'>
                                        <div class='col-sm-10 col-sm-offset-4'>
                                            <input class='btn btn-primary' type='submit' name='submit' value='{{ trans('langModify') }}'>
                                        </div>
                                    </div>
                                </fieldset>
                            </form>
                        </div>  
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>              
@endsection