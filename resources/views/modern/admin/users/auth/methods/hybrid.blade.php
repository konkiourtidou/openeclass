                <div class='row p-2'></div>
                
                <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12'>
                   <div class='alert alert-info'>{{ ucfirst(trans('langAuthenticateVia')) }} {{ $auth_data['auth_name'] }}</div>
                   <div class='row p-2'></div>
                </div>

                <div class='form-group'>
                    <label for='hybridauth_id_key' class='col-sm-6 control-label-notes'>{{ ucfirst($auth_data['auth_name']) }} Id/Key:</label>
                    <div class='col-sm-12'>
                        <input class='form-control' name='hybridauth_id_key' id='hybridauth_id_key' type='text' value='{{ isset($auth_data['key']) ? $auth_data['key'] : '' }}'>
                    </div>
                </div> 

                <div class='row p-2'></div>

                <div class='form-group'>
                    <label for='hybridauth_secret' class='col-sm-6 control-label-notes'>{{ ucfirst($auth_data['auth_name']) }} Secret:</label>
                    <div class='col-sm-12'>
                        <input class='form-control' name='hybridauth_secret' id='hybridauth_secret' type='text' value='{{ isset($auth_data['secret']) ? $auth_data['secret'] : '' }}'>
                    </div>
                </div> 

                <div class='row p-2'></div>

                <div class='form-group'>
                    <label for='auth_instructions' class='col-sm-6 control-label-notes'>{{ trans('langInstructionsAuth') }}:</label>
                    <div class='col-sm-12'>
                        <textarea class='form-control' name='hybridauth_instructions' id='hybridauth_instructions' rows='10'>{{ $auth_data['auth_instructions'] }}</textarea>
                    </div>
                </div>