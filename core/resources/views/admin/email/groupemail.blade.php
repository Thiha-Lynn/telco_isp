@extends('admin.layout')

@section('content')

<div class="content-header">
    <div class="container-fluid">
        <div class="row">
        <div class="col-sm-6">
            <h1 class="m-0 text-dark">{{ __('Group Email') }}</h1>
        </div><!-- /.col -->
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard', app()->getLocale()) }}"><i class="fas fa-home"></i>{{ __('Home') }}</a></li>
            <li class="breadcrumb-item">{{ __('Group Email') }}</li>
            </ol>
        </div><!-- /.col -->
        </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</div>

@if(Session::has('notification'))
<div class="alert alert-{{ Session::get('notification')['alert'] }} alert-dismissible">
    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
    <strong>{{ ucfirst(Session::get('notification')['alert']) }}!</strong> {{ Session::get('notification')['messege'] }}
</div>
@endif

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title mt-1">{{ __('Send Group Email') }}</h3>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <form action="{{ route('admin.group.submit', app()->getLocale()) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="subject">{{ __('Subject') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="subject" name="subject" required placeholder="{{ __('Enter email subject') }}">
                            </div>
                            <div class="form-group">
                                <label for="message">{{ __('Message') }} <span class="text-danger">*</span></label>
                                <textarea class="form-control summernote" id="message" name="message" rows="10" required placeholder="{{ __('Enter email message') }}"></textarea>
                            </div>
                            <div class="form-group">
                                <label>{{ __('Recipients') }}</label>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="all_users" name="all_users" value="1">
                                    <label class="custom-control-label" for="all_users">{{ __('All Users') }}</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="active_users" name="active_users" value="1">
                                    <label class="custom-control-label" for="active_users">{{ __('Active Users Only') }}</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Send Email') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
