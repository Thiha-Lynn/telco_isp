<nav class="main-header navbar navbar-expand navbar-light navbar-white">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
      </li>
    </ul>
    <ul class="navbar-nav" style="margin-left:70%;">
        <li class="nav-item dropdown">
        <a class="ok" data-toggle="dropdown" href="#" style="margin: 8px;">
            <i class="fas fa-bell" style="height: 0px; border-radius: 68%; background: #2a7cce; padding: 6px 7px 23px; border-color: yellow;" aria-hidden="true">
                <b class="badge badge-info"></b>
                <span style="color: #f4f6f9; padding: 2px; background: #ffa700; position: absolute;">
                    <?php 
                        $count = DB::table('chat')->select(DB::raw('count(*) as count'))->where('read_status', '=', 0)->where('sender_userid', '!=', 1 )->count();
                        if($count==0){ 
                            echo ""; 
                        }else { 
                            echo $count;
                        } 
                    ?>
                </span>
            </i>
        </a>
        
        
        
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right border-none" style="    margin-top: 10px;">
              <div class="card card-widget widget-user-2 mb-0 shadow-none">
                <!-- Add the bg color to the header using any of the bg-* classes -->
              
                <div class="card-footer p-0 bg-white">
                  <ul class="nav flex-column" >
                      <?php 
         $username = DB::table('chat')->select('sender_userid')
                   ->where('sender_userid','!=',1)
                   ->where('read_status', '=', 0)
                   ->distinct()
                   ->get();
         foreach($username as $value)
            { ?>
                    <li class="nav-item"  value="{{$value->sender_userid}}" onClick="getuserid(this.value);"> 
                      <a href="" class="nav-link">
                          {{App\Models\User::find($value->sender_userid)->name??''}}- <b class="badge badge-info">{{DB::table('chat')->where('read_status', '=', 0)->where('sender_userid',$value->sender_userid)->count()??''}}</b>
                      </a>
                    </li>
                       <?php } ?>
                  </ul>
                </div>
              </div>
        </div>
      </li>
      <script>
        function getuserid(val) {
                   $('#user_id').val(val);
                            $.ajax({
                                     type:'get',
                                     url:'https://telco.mbt.com.mm/en/admin/user-message-notification',
                                     data: 'yearname=' + val,
                                     headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                                     success:function(data) 
                                     {
                                          window.location = "https://telco.mbt.com.mm/en/admin/message-to-user";
                                     }
                                  });
                        }

        </script>
     @php
        // Get all current route parameters and merge with the new locale
        $currentRouteParams = request()->route() ? request()->route()->parameters() : [];
     @endphp
     @foreach (config('app.available_locales') as $locale)
     @php
        // Merge the current route parameters with the new locale
        $switchParams = array_merge(['locale' => $locale], $currentRouteParams);
     @endphp
     @if($locale == 'en')
     <img class="user-image w-40 img-circle " src="{{ asset('assets/admin/img/eng.jpg') }}"  alt="User Image" style="max-width: 18px;max-height: 18px;margin-top: 10px;">
      <li class="nav-item">
           <a class="nav-link" href="{{ route(\Illuminate\Support\Facades\Route::currentRouteName(), $switchParams) }}" @if (app()->getLocale() == $locale) style="font-weight: bold; text-decoration: underline" @endif>
           {{ strtoupper($locale) }} </a>
        </li>
     @else
     <img class="user-image w-40 img-circle " src="{{ asset('assets/admin/img/china.jpg') }}"  alt="User Image" style="max-width: 18px;max-height: 18px;margin-top: 10px;">
        <li class="nav-item">
           <a class="nav-link" href="{{ route(\Illuminate\Support\Facades\Route::currentRouteName(), $switchParams) }}" @if (app()->getLocale() == $locale) style="font-weight: bold; text-decoration: underline" @endif>
           {{ strtoupper($locale) }} </a>
        </li>
     @endif
       
    @endforeach
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <!-- Notifications Dropdown Menu -->
      <li class="nav-item dropdown">
        <a class="nav-link pt-0 pr-3 " data-toggle="dropdown" href="#">
            <img class="user-image w-40 img-circle shadow" src="{{ asset('assets/front/img/'.Auth()->guard('admin')->user()->image) }}"  alt="User Image">
        </a>
        
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right border-none">
              <div class="card card-widget widget-user-2 mb-0 shadow-none">
                <!-- Add the bg color to the header using any of the bg-* classes -->
                <div class="widget-user-header bg-primary">
                  <div class="widget-user-image bg-white">
                    <img class="img-circle elevation-2  bg-white" src="{{ asset('assets/front/img/'.Auth()->guard('admin')->user()->image) }}" alt="User Avatar">
                  </div>
                  <!-- /.widget-user-image -->
                  <h6 class="widget-user-username mt-2">{{ Auth()->guard('admin')->user()->name }}</h6>
                  <h6 class="widget-user-desc">{{ Auth()->guard('admin')->user()->email }}</h6>
                </div>
                <div class="card-footer p-0 bg-white">
                  <ul class="nav flex-column">
                    <li class="nav-item"> 
                      <a href="{{ route('admin.editProfile',app()->getLocale() ) }}" class="nav-link">
                          <i class="fas fa-edit mr-1"></i> {{ __('Edit Profile') }} 
                      </a>
                    </li>
                    <!-- <li class="nav-item"> -->
                    <!--  <a href="#" class="nav-link">-->
                    <!--      <i class="fas fa-language mr-1"></i> {{ __('Language') }} -->
                    <!--  </a>-->
                    <!--</li>-->
                    <li class="nav-item">
                      <a href="{{ route('admin.editPassword' ,app()->getLocale()) }}" class="nav-link">
                          <i class="fas fa-unlock-alt mr-1"></i> {{ __('Change Password') }}
                      </a>
                    </li>
                    <li class="nav-item">
                      <a href="{{route('admin.logout' ,app()->getLocale())}}" class="nav-link">
                          <i class="fas fa-sign-out-alt mr-1"></i> {{ __('Logout') }}
                      </a>
                    </li>
                  </ul>
                </div>
              </div>
        </div>
      </li>
    </ul>
  </nav>
<script>
$('#birds a').on('click', function (e) {
  e.preventDefault()
  $(this).tab('show')
})
</script>