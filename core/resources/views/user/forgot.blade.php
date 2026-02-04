@extends('front.layout')

@section('meta-keywords', "$setting->meta_keywords")
@section('meta-description', "$setting->meta_description")
@section('content')

	<!--Main Breadcrumb Area Start -->
	<div class="main-breadcrumb-area" style="background-image : url('{{ asset('assets/front/img/' . $commonsetting->breadcrumb_image) }}');">
        <div class="overlay"></div>
		<div class="container">
			<div class="row">
				<div class="col-lg-12">
					<h1 class="pagetitle">
						{{ __('Forgot Password') }}
					</h1>
					<ul class="pages">
						<li>
							<a href="{{ route('front.index') }}">
								{{ __('Home') }}
							</a>
						</li>
						<li class="active">
							<a href="#">
								{{ __('Forgot Password') }}
							</a>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
	<!--Main Breadcrumb Area End -->

        <!-- Forgot Password Area Start -->
        <section class="auth">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-6 col-md-10">
                        <div class="sign-form">
                            <div class="heading">
                                <h4 class="title">
                                    {{ __('Forgot Password') }}
                                </h4>
                                <p class="subtitle">
                                    {{ __('Enter your email address and we will send you a link to reset your password.') }}
                                </p>
                            </div>

                            @if(session('success'))
                                <div class="alert alert-success">
                                    {{ session('success') }}
                                </div>
                            @endif

                            @if(session('error'))
                                <div class="alert alert-danger">
                                    {{ session('error') }}
                                </div>
                            @endif

                            <form class="form-group mb-0" action="{{ route('user.forgot.submit') }}" method="POST">
                                @csrf
                                <div class="form-field">
                                    <input type="email" name="email" placeholder="{{ __('Email Address') }}" value="{{ old('email') }}" required>
                                    @error('email')
                                        <p class="text-danger">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="form-btn mb-5">
                                    <button type="submit" class="btn">{{ __('Send Reset Link') }}</button>
                                </div>
                            </form>
                            
                            <p class="text-center">
                                {{ __('Remember your password?') }} 
                                <a href="{{ route('user.login') }}">{{ __('Login') }}</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- Forgot Password Area End -->

@endsection
