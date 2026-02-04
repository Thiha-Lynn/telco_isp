<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<meta name="description" content="@yield('meta-description')">
	<meta name="keywords" content="@yield('meta-keywords')">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	
    <title>{{ $setting->website_title }}</title>

    <!-- favicon -->
	<link rel="shortcut icon" href="{{ asset('assets/front/img/' . $commonsetting->fav_icon) }}" type="image/x-icon">
	<!-- Google Front -->
	<link rel="preconnect" href="https://fonts.gstatic.com">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,400;0,500;0,600;0,700;1,800&display=swap" rel="stylesheet">
    <!-- bootstrap -->
    <link rel="stylesheet" href="{{ asset('/') }}assets/front/css/bootstrap.min.css">
    <!-- Plugin css -->
    <link rel="stylesheet" href="{{ asset('/') }}assets/front/css/plugin.css">
    <!-- Sweetalert2 css -->
	<link rel="stylesheet" href="{{ asset('assets/admin/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">

	@yield('style')
    <!-- stylesheet -->
    <link rel="stylesheet" href="{{ asset('/') }}assets/front/css/style.css">
    <!-- responsive -->
    <link rel="stylesheet" href="{{ asset('/') }}assets/front/css/responsive.css">
	<!-- dynamic Style change -->
	<link rel="stylesheet" href="{{ asset('assets/front/css/dynamic-css.css') }}">
	<link href="{{ url('/') }}/assets/front/css/dynamic-style.php?color={{ $commonsetting->base_color }}" rel="stylesheet">
	@if($currentLang->direction == 'rtl')
	<!-- RTL css -->
	<link rel="stylesheet" href="{{ asset('/') }}assets/front/css/rtl.css">
	@endif
	
</head>

<body {{ Session::has('notification') ? 'data-notification' : '' }} @if(Session::has('notification')) data-notification-message='{{ json_encode(Session::get('notification')) }} @endif' >

    <!-- preloader area start -->
    <div class="preloader" id="preloader">
        <div class="loader loader-1">
            <div class="loader-outter"></div>
            <div class="loader-inner"></div>
        </div>
    </div>
    <!-- preloader area end -->

	<!--Main-Menu Area Start-->
	<div class="mainmenu-area">
		<!-- Top Menu -->
		<div class="top-header">
			<div class="container">
				<div class="row">
					<div class="col-md-6 align-self-center d-none d-lg-block">
						<div class="left-content">
							<ul>
								<li>
									<a href="#">
										<i class="fas fa-phone"></i>
										@php
										$number = explode( ',', $commonsetting->number );
										@endphp
										{{ $number[0] }}
									</a>
								</li>
								<li>
									<a href="#">
										<i class="fas fa-envelope"></i>
										@php
										$number = explode( ',', $commonsetting->email );
										@endphp
										{{ $number[0] }}
									</a>
								</li>
							</ul>
						</div>
					</div>
					<div class="col-lg-6 align-self-center">
						<div class="right-content">
							<ul>

								@if(auth()->check())
									<li>
										<a href="{{ route('user.dashboard') }}"><i class="fas fa-user"></i> {{ Auth::user()->name }}</a>
									</li>
								@else
									<li>
										<a href="{{ route('user.login') }}">{{ __('Login') }}</a>
									</li>
									<li>
										<a href="{{ route('user.register') }}">{{ __('Register') }}</a>
									</li>
								@endif
								@if (count($langs) > 0)
								<li class="language-change">
									<p class="name"><i class="fas fa-globe"></i>{{ $currentLang->name }}</p>
									<div class="language-menu">
										@foreach ($langs as $lang)
										<a href="{{ route('changeLanguage', $lang->code) }}" class="{{ $lang->name == $currentLang->name ? 'active' : '' }}">{{ $lang->name }}</a>
										@endforeach
									</div>
								</li>
								@endif
								<li>
									@if(Auth::user())
									<a href="{{ route('front.billpay') }}" class="mybtn1">{{ __('Pay Bill') }}</a>
									@else
									<a href="{{ route('user.login') }}" class="mybtn1">{{ __('Pay Bill') }}</a>
									@endif
								</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="container">
			<div class="row">
				<div class="col-lg-12">
					<nav class="navbar navbar-expand-lg navbar-light">
						<a class="navbar-brand" href="{{ route('front.index') }}">
							<img src="{{ asset('assets/front/img/'.$commonsetting->header_logo) }}" alt="">
						</a>
						<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#main_menu" aria-controls="main_menu"
							aria-expanded="false" aria-label="Toggle navigation">
							<span class="navbar-toggler-icon"></span>
						</button>
						<div class="collapse navbar-collapse fixed-height" id="main_menu">
							<ul class="navbar-nav ml-auto">
								<li class="nav-item">
									<a class="nav-link @if(request()->path() == '/') active  @endif" href="{{ route('front.index') }}">{{ __('Home') }}</a>
								</li>
								@if($commonsetting->is_about_page)
								<li class="nav-item">
									<a class="nav-link @if(request()->path() == 'about') active  @endif" href="{{ route('front.about') }}">{{ __('About') }}</a>
								</li>
								@endif
								<li class="nav-item">
									<a class="nav-link 
									@if(request()->path() == 'service') active  
									@elseif(request()->is('service/*')) active
									@endif" href="{{ route('front.service') }}">{{ __('Service') }}</a>
								</li>
								<li class="nav-item">
									<a class="nav-link @if(request()->path() == 'package') active  @endif" href="{{ route('front.package') }}">{{ __('Package') }}</a>
								</li>
								@if($commonsetting->is_media_page)
								<li class="nav-item">
									<a class="nav-link @if(request()->path() == 'media') active  @endif" href="{{ route('front.media') }}">{{ __('Media') }}</a>
								</li>
								@endif
								@if($commonsetting->is_shop_page)
								<li class="nav-item dropdown">
									<a class="nav-link 
									@if(request()->path() == 'shop') active  
									@endif
									 dropdown-toggle" href="{{ route('front.products') }}">
										{{ __('Shop') }}
									</a>
									<div class="dropdown-menu">
										<a class="dropdown-item" href="{{ route('front.products') }}">{{ __('All Product') }}</a>
										<a class="dropdown-item" href="{{ route('front.cart') }}">
											{{ __('Cart') }}
										</a>
									</div>
								</li>
								@endif
								<li class="nav-item dropdown">
									<a class="nav-link 
									@if(request()->path() == 'faq') active  
									@elseif(request()->path() == 'team') active
									@elseif(request()->path() == 'branch') active
									@elseif(request()->path() == 'blog') active  
									@elseif(request()->is('blog-details/*')) active
									@endif
									 dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
										{{ __('Pages') }}
									</a>
									<div class="dropdown-menu">
										@if($commonsetting->is_faq_page)
										<a class="dropdown-item" href="{{ route('front.faq') }}">{{ __('Faq') }}</a>
										@endif
										@if($commonsetting->is_team_page)
										<a class="dropdown-item" href="{{ route('front.team') }}">{{ __('Team') }}</a>
										@endif
										@if($commonsetting->is_branch_page)
										<a class="dropdown-item" href="{{ route('front.branch') }}">{{ __('Branch') }}</a>
										@endif
										@if($commonsetting->is_blog_page)
										<a class="dropdown-item" href="{{ route('front.blogs') }}">{{ __('Blog') }}</a>
										@endif
										@foreach ($front_dynamic_pages as $dynamicpage)
											<a class="dropdown-item" href="{{ route('front.front_dynamic_page', $dynamicpage->slug) }}">{{ $dynamicpage->title }}</a>
										@endforeach
										
									</div>
								</li>
								@if($commonsetting->is_speed_test)
								<li class="nav-item">
									<a class="nav-link @if(request()->path() == 'speed-test') active  @endif" href="{{ route('front.speed.test') }}">{{ __('Speed Test') }}</a>
								</li>
								@endif
								@if($commonsetting->is_contact_page)
								<li class="nav-item">
									<a class="nav-link @if(request()->path() == 'contact') active  @endif" href="{{ route('front.contact') }}">{{ __('Contact') }}</a>
								</li>
								@endif
							</ul>
						</div>
					</nav>
				</div>
			</div>
		</div>
	</div>
	<!--Main-Menu Area Start-->


	@yield('content')

	<!-- Footer Area Start -->
	<footer class="footer" id="footer">
		<div class="container">
			<div class="row">
				<div class="col-md-6 col-lg-4">
					<div class="footer-widget about-widget">
						<div class="footer-logo">
							<a href="#">
								<img src="{{ asset('assets/front/img/'.$commonsetting->header_logo) }}" alt="">
							</a>
						</div>
						<div class="text">
							<p>
								{{ $setting->footer_text }}
							</p>
						</div>

					</div>
				</div>
				<div class="col-md-6 col-lg-4">
					<div class="footer-widget address-widget">
						<h4 class="title">
							{{  __('Address') }}
						</h4>
						<ul class="about-info">
							<li>
								<p>
										<i class="fas fa-globe"></i>
									{{ $setting->address }}
								</p>
							</li>
							<li>
								<p>
										<i class="fas fa-phone"></i>
										@php
										$number = explode( ',', $commonsetting->number );
										for ($i=0; $i < count($number); $i++) {
											echo $number[$i].', ';
										}
										@endphp
								</p>
							</li>
							<li>
								<p>
										<i class="far fa-envelope"></i>
										@php
										$email = explode( ',', $commonsetting->email );
										for ($i=0; $i < count($email); $i++) {
											echo $email[$i].', ';
										}
										@endphp
								</p>
							</li>
						</ul>
					</div>
				</div>
				<div class="col-md-6 col-lg-4">
						<div class="footer-widget  footer-newsletter-widget">
							<h4 class="title">
								{{ __('Newsletter') }}
							</h4>
							<div class="newsletter-form-area">
								<form action="{{ route('front.newsletter') }}" method="POST">
									@csrf
									<input type="email" name="email" placeholder="{{  __('Email Address') }}">
									<button type="submit">
										<i class="far fa-paper-plane"></i>
									</button>
								</form>
							</div>
							<div class="social-links">
								<h4 class="title">
									{{ __('Connect with us on social media :') }}
								</h4>
								<div class="fotter-social-links">
									<ul>
										@foreach($socials as $key => $social)
										<li>
											<a href="{{ $social->url }}">
												<i class="{{ $social->icon }}"></i>
											</a>
										</li>
										@endforeach
									</ul>
								</div>
							</div>

						</div>
				</div>
			</div>
		</div>
		<div class="copy-bg">
			<div class="container">
				<div class="row">
					<div class="col-lg-12">
							<div class="content">
								<div class="content">
									{!! $setting->copyright_text !!}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</footer>
	<!-- Footer Area End -->

 <!-- Back to Top Start -->
 <div class="bottomtotop">
  <i class="fas fa-chevron-right"></i>
 </div>
 <!-- Back to Top End -->

	{{-- Cookie alert dialog start --}}
	@if ($setting->is_cooki_alert == 1)
		@include('cookie-consent::index')
	@endif
	{{-- Cookie alert dialog end --}}

 <input type="hidden" id="main_url" value="{{ route('front.index') }}">


 <!-- jquery -->
 <script src="{{ asset('/') }}assets/front/js/jquery.js"></script>
 <!-- bootstrap -->
 <script src="{{ asset('/') }}assets/front/js/bootstrap.min.js"></script>
 <!-- popper -->
 <script src="{{ asset('/') }}assets/front/js/popper.min.js"></script>
 <!-- plugin js-->
 <script src="{{ asset('/') }}assets/front/js/plugin.js"></script>
 <!-- Sweetalert2 js -->
 <script src="{{ asset('assets/admin/plugins/sweetalert2/sweetalert2.min.js') }}"></script>

 @yield('script')
 <!-- main -->
 <script src="{{ asset('/') }}assets/front/js/main.js"></script>


 @if($commonsetting->is_tawk_to	== 1)
	{!!  $commonsetting->tawk_to !!}
@endif


</body>

</html>
