                    <div class="panel panel-primary margin-top-fat mt-3">
                        <div class="panel-heading">
                            <h4 class="panel-title text-white">                
                                {{ trans('langPortfolioMainContent') }}
                            </h4>
                        </div>                  
                        <div class="panel-body panel-body-admin ps-3 pt-3 pb-3 pe-3" id="portfolio_widget_main" data-widget-area-id="3">
                            @foreach ($portfolio_main_area_widgets as $key => $portfolio_main_area_widget)
                            <div class="panel{{!isset($myWidgets) || isset($myWidgets) && $portfolio_main_area_widget->is_user_widget ? ' panel-success widget' : ' panel-default'}}" data-widget-id="{{ $portfolio_main_area_widget->id }}" data-widget-widget-area-id="{{ $key }}">
                                <div class="panel-heading">                   
                                    <a class='text-white' data-bs-toggle="collapse" data-bs-target="#widget_desc_{{ $key }}" 
                                       href="#widget_desc_{{ $key }}" class="widget_title">
                                        {{ $portfolio_main_area_widget->getName() }} <span></span> <small class="pull-right">{{ $portfolio_main_area_widget->is_user_widget ? trans('langWidgetPersonal') : trans('langWidgetAdmin') }}</small>
                                    </a>                     
                                </div>
                                @if (!isset($myWidgets) || isset($myWidgets) && $portfolio_main_area_widget->is_user_widget)
                                <div class="panel-collapse collapse in">
                                    <div class="panel-body panel-body-admin ps-3 pt-3 pb-3 pe-3">
                                        {!! $portfolio_main_area_widget->getOptionsForm($key) !!}
                                    </div>
                                    <div class="panel-footer clearfix">
                                        <a href="#" class="remove">
                                            <small>{{ trans('langDelete') }}</small>
                                        </a>
                                        <div class="pull-right">
                                            <a href="#" class="btn btn-xs btn-primary submitOptions">
                                                {{ trans('langSubmit') }}
                                            </a>                                
                                        </div>                    
                                    </div>                        
                                </div>
                                @endif
                            </div>                
                            @endforeach
                        </div>
                    </div>

