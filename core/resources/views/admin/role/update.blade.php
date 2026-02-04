@extends('admin.layout')

@section('content')

    <div class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">{{ __('Role update') }} </h1>

                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard',app()->getLocale()) }}"><i class="fas fa-home"></i>{{ __('Home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{route('admin.role_manage',app()->getLocale())}}"><i class="fas fa-user"></i>{{ __('Update Role') }}</a></li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    @if ($errors->any())
  <div class="alert alert-danger" style="width: fit-content;">
     <ul>
        @foreach ($errors->all() as $error)
           <li>{{ $error }}</li>
        @endforeach
     </ul>
     @if ($errors->has('email'))
     @endif
  </div>
@endif
  @if(Session::has('message'))
    <div class="alert alert-success alert-dismissible" style="width: fit-content;">
      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
      <strong>Success!</strong> {{Session::get('message')}}
    </div>
    @endif
    <section class="content">
        <div class="container-fluid">
             
         <div class="row">
            
        <div class="col-md-8">

                    <div class="card card-primary card-outline toggle" style="border-radius:0px!important;">
                       
                        <div class="card-body">
                           
                             <form class="form-horizontal" action="{{ route('admin.edit_role',app()->getLocale()) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="role_id" value="{{$role->id}}">
                                   
                                        <div class="row">
                                         <div class="col-md-4">
                                            <label class="col-sm-4 control-label">{{ __('Role Name') }}<span class="text-danger">*</span></label>
                                             <div class="col-sm-12">
                                                <input type="text" name="role_name" value="{{$role->name}}" class="form-control" placeholder="Enter role name..." required>
                                              </div> 
                                            </div>
                                            <!-- Password fields removed - not needed for role management -->
                                        </div>
                                         <div class="row">
                                        <div class="col-md-12">
                                        <label class="col-sm-4 control-label">{{ __('Permission') }}<span class="text-danger">*</span></label>
                                           <div class="col-sm-8">
                                             @foreach($permissions as $value)
                                               <input class="perm" name="permission[]" type="checkbox" value="{{$value->id}}" <?php if(in_array($value->id,$array)){ echo "checked";}?> />&nbsp;&nbsp;{{$value->name}}</br>
                                             @endforeach
                                           </div> 
                                        </div>
                                     </div>
                                  
                                    <div class="form-group row mr-8" >
                                        <div class="offset-sm-2 col-sm-10" style="margin-left: 0.667%;margin-top: 11px;">
                                           <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                                        </div>
                                    </div>
                                
                                </form>
                          
                    </div>
                     </div>
                </div>    
                  <script>
                           function myFunction() {
                              var x = document.getElementById("myInput");
                              if (x.type === "password") {
                                x.type = "text";
                              } else {
                                x.type = "password";
                              }
                            }
                           </script>
                           <script>
                           $(document).ready(function(){
                                
                             $('#error').hide();
                              $("#confpassword").keyup(function(){
                                  //alert('dfgdfgs');
                                  var conf = $('#confpassword').val();
                                  var pass = $('#myInput').val();
                                  if(pass == conf)
                                  {
                                      //alert('ok');
                                     $('#error').hide();
                                      $('#sub').prop('disabled', false);
                                  }else
                                  {
                                       //alert('not');
                                       $('#error').show();
                                      $('#sub').prop('disabled', true);
                                  }
                              });
                            });
                            </script>
                <div class="col-md-4">

                    <div class="card card-primary card-outline toggle" style="border-radius:0px!important;">
                         <div class="card-header">
                           <a href="{{route('admin.role_manage',app()->getLocale())}}" class="badge badge-danger" style="float: right;"><i class="fa fa-arrow-left" aria-hidden="true"></i>&nbsp;
                             {{__('Back')}}</a>
                        </div>
                        <div class="card-body">
                           <center><b>{{__('How to Create And Set Permission')}}</b></center>
                             <p><li>{{__('By default, new roles have no permissions')}}.</li></p>
                             <p><li>{{__('Therefore, when you create a role, you must add all the permissions for that role')}}.</li></p>
                             <p><li>{{__('In the Create User Role dialog box, enter a name for the role, and check or set permission and then click Save')}}.</li></p>
                             <p><li>{{__('For example, if you do not add permission to view the pages of the server user interface in the Web UI section, users can log in to the server but cannot see any information')}}.</li></p>
                        </div>
                    </div>
                </div> 
          
        
        </div>
         </div>
    </section>
@endsection


