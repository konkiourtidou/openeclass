@extends('layouts.default')

@section('content')

<div class="pb-3 pt-3">

    <div class="container-fluid main-container">

        <div class="row">

            <div class="col-xl-2 col-lg-2 col-md-0 col-sm-0 col-0 justify-content-center col_sidebar_active"> 
                <div class="d-none d-sm-block d-sm-none d-md-block d-md-none d-lg-block">
                    @include('layouts.partials.sidebarAdmin')
                </div>
            </div>

            <div class="col-xl-10 col-lg-10 col-md-12 col-sm-12 col-12 justify-content-center col_maincontent_active">
                    
                <div class="row p-5">

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
                        @include('layouts.partials.sidebarAdmin')
                        </div>
                    </div>

                    @if($breadcrumbs && count($breadcrumbs)>2)
                    <div class='row p-2'></div>
                    <div class="float-start">
                        <p class='control-label-notes'>{!! $breadcrumbs[1]['bread_text'] !!}</p>
                        <small class='text-secondary'>{!! $breadcrumbs[count($breadcrumbs)-1]['bread_text'] !!}</small>
                    </div>
                    <div class='row p-2'></div>
                    @endif

                    {!! isset($action_bar) ?  $action_bar : '' !!}

                    @if (count($wc_servers) > 0)
                    <div class='table-responsive'>
                    <table class='announcements_table'>
                    <thead>
                        <tr class='notes_thead'>
                            <th class = 'text-white text-center'>{{ trans('langWebConfServer') }}</th>
                            <th class = 'text-white text-center'>{{ trans('langWebConfScreenshareServer') }}</th>
                            <th class = 'text-white text-center'>{{ trans('langBBBEnabled') }}</th>
                            <th class = 'text-white text-center'>{!! icon('fa-gears') !!}</th>
                        </tr>
                    </thead>
                    @foreach ($wc_servers as $wc_server)
                        <tr>
                            <td>{{ $wc_server->hostname }}</td>
                            <td>{{ $wc_server->screenshare }}</td>
                            <td class='text-center'>
                                {{ $wc_server->enabled=='true' ? trans('langYes') : trans('langNo') }}
                            </td>
                            <td class='option-btn-cell text-center'>
                                {!! action_button([
                                            [
                                                'title' => trans('langEditChange'),
                                                'url' => "$_SERVER[SCRIPT_NAME]?edit_server=$wc_server->id",
                                                'icon' => 'fa-edit'
                                            ],
                                            [
                                                'title' => trans('langDelete'),
                                                'url' => "$_SERVER[SCRIPT_NAME]?delete_server=$wc_server->id",
                                                'icon' => 'fa-times',
                                                'class' => 'delete',
                                                'confirm' => trans('langConfirmDelete')
                                            ]
                                        ]) !!}
                            </td>
                        </tr>
                    @endforeach            	
                    </table>
                    </div>
                    @else 
                       <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12'>
                           <div class='alert alert-warning'>{{ trans('langNoAvailableBBBServers') }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>    
@endsection