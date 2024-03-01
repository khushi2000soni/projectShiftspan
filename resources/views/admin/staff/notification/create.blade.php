<div class="modal fade common-modal modal-size-l" id="NnotificationSettings" tabindex="-1" aria-labelledby="NnotificationSettingsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-820">
        <div class="modal-content">
            <div class="modal-header justify-content-center blue-bg">
                <h5 class="modal-title text-center" id="NnotificationSettingsLabel">@lang('cruds.notification.fields.notification_settings')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body inner-size-l">
                <form class="msg-form" id="addNotificationForm" action="" {{-- method="POST" --}} enctype='multipart/form-data'>
                    @csrf
                    <div class="form-label">
                        <label>@lang('cruds.notification.fields.staff')</label>
                        <div class="right-sidebox-small modal-dropdown">
                            <div class="select-box">
                                <span class="selected-options">@lang('global.select') ...</span>
                            </div>
                            <div class="options">
                                <p class="selectAll">@lang('cruds.notification.fields.all_staff')</p>
                                <input type="text" id="searchInput" placeholder="Search...">
                                <ul class="custom-check">
                                    @foreach ($staffsNotifify as $key=>$item)
                                        <li class="select-option">
                                            <label>
                                                <input type="checkbox" name="staffs[]" class="checkboxes" value="{{$key}}">
                                                <span>{{$item}}</span>
                                            </label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>                          
                        </div>
                        <span class="staffs_error"></span>
                    </div>
                    <div class="form-label select-label">
                        <label for="notification_subject">@lang('cruds.notification.fields.section'):</label>
                        <select class="select2" name="section" id="section">
                            <option value="">@lang('global.select')  ...</option>
                            @foreach (config('constant.notification_subject') as $key=>$val)
                                <option value="{{$key}}">{{ $val }}</option>
                            @endforeach
                        </select>
                        <span class="section_error"></span>
                    </div>
                    <div class="form-label">
                        <label class="text-end px-2">@lang('cruds.notification.fields.subject'): </label>
                        <input type="text" name="subject" value="" placeholder="Type.......">
                    </div>
                    <div class="form-label with-textarea">
                        <label>@lang('cruds.notification.fields.message'):</label>
                        <textarea placeholder="Type......." name="message"></textarea>
                    </div>
                    <div class="form-label justify-content-center">
                        <input type="submit" value="@lang('global.send')" id="" class="cbtn submitBtn">
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>