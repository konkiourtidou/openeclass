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
                        <div class='form-wrapper shadow-sm p-3 mt-5 rounded'>
                            
                            <form class='form-horizontal' action='{{ $_SERVER['SCRIPT_NAME'] }}' method='post'>
                                <div class='form-group mt-3'>
                                    <label class='col-sm-6 control-label-notes'>{{ trans('langAutoJudgeConnector') }}:</label>
                                    <div class='col-sm-12'>
                                        <select class='form-select' name='formconnector'>{!! implode('', $connectorOptions) !!}</select>
                                    </div>
                                </div>
                                @foreach($connectorClasses as $curConnectorClass)
                                    <div class='form-group connector-config connector-{{ $curConnectorClass }} mt-3' style='display: none;'>
                                        <label class='col-sm-6 control-label-notes'>{{ trans('langAutoJudgeSupportedLanguages') }}:</label>
                                        <div class='col-sm-12'>
                                            {!! implode(', ', array_keys((new $curConnectorClass)->getSupportedLanguages())) !!}</div>
                                    </div>
                                    <div class='form-group connector-config connector-{{ $curConnectorClass }} mt-3' style='display: none;'>
                                        <label class='col-sm-6 control-label-notes'>{{ trans('langAutoJudgeSupportsInput') }}:</label>
                                        <div class='col-sm-12'>
                                            {{ (new $curConnectorClass)->supportsInput() ? trans("langCMeta['true']") : trans("langCMeta['false']") }}
                                        </div>
                                    </div>
                                    @foreach((new $curConnectorClass())->getConfigFields() as $curField => $curLabel)
                                        <div class='form-group connector-config connector-{{ $curConnectorClass }} mt-3' style='display: none;'>
                                            <label class='col-sm-6 control-label-notes'>{{ $curLabel }}:</label>
                                            <div class='col-sm-12'><input class='FormData_InputText' type='text' name='form$curField' size='40' value='{{ get_config($curField) }}'></div>
                                        </div>
                                    @endforeach
                                @endforeach
                                <div class='form-group mt-3'>
                                    <div class='col-sm-offset-3'>
                                        {!! form_buttons(array(
                                            array(
                                                'text' => trans('langModify'),
                                                'name' => 'submit',
                                                'value'=> trans('langModify')
                                            ),
                                            array(
                                                'href' => "extapp.php"
                                            )
                                        )) !!}
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection