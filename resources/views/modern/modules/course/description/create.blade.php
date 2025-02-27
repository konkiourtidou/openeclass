@extends('layouts.default')

@section('content')

<div class="pb-lg-3 pt-lg-3 pb-0 pt-0">

    <div class="container-fluid main-container pb-3">

        <div class="row rowMedium">

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

                    

                    {!! $action_bar !!}
                    <div class='col-12'>
                        <div class='form-wrapper shadow-sm p-3 rounded'>
                            
                            <form class='form-horizontal' role='form' action='{{$urlServer}}modules/course_description/index.php?course={{ $course_code }}' method='post'>
                                <input type='hidden' name='course' value='{{ $course_code }}'>
                                @if ($editId)
                                    <input type='hidden' name='editId' value='{{ getIndirectReference($editId) }}'>
                                @endif            

                            

                                <div class='form-group mt-3'>
                                    <label for='editType' class='col-sm-6 control-label-notes'>{{ trans('langType') }}: </label>
                                    <div class='col-sm-12'>
                                        {!! selection($types, 'editType', $defaultType, 'class="form-control" id="typSel"') !!}
                                    </div>
                                </div>

                                


                                <div class='form-group{{ $titleError ? " form-error" : ""}} mt-3'>
                                    <label for='titleSel' class='col-sm-6 control-label-notes'>{{ trans('langTitle') }}:</label>
                                    <div class='col-sm-12'>
                                        <input type='text' name='editTitle' class='form-control' value='{{ $cdtitle }}' size='40' id='titleSel'>
                                        {!! Session::getError('editTitle', "<span class='help-block'>:message</span>") !!}                                    
                                    </div>
                                </div>      
                                
                            


                                <div class='form-group mt-3'>
                                    <label for='editComments' class='col-sm-6 control-label-notes'>{{ trans('langContent') }}:</label>
                                    <div class='col-sm-12'>
                                    {!! $text_area_comments !!}
                                    </div>
                                </div>

                                

                                <div class='form-group mt-3'>    
                                    <div class='col-sm-10 col-sm-offset-2'>
                                        {!! $form_buttons !!}
                                    </div>
                                </div>
                            {!! generate_csrf_token_form_field() !!}                              
                            </form>
                        </div>  
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
        
        
@endsection

