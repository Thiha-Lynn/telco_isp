<!DOCTYPE html>
<html lang="en">
<head>
  <title>Privacy policy</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</head>
<body>

<section class="pad top_margin">
            <div class="container">
                
<h2>Privacy policy</h2>

<?php $privacy_policy = DB::table('daynamicpages')->where('id',9)->first(); ?>
@if($privacy_policy)
    {!! $privacy_policy->content !!}
@else
    <p>Privacy policy content is not available.</p>
@endif
            </div>
        </section>
        
</body>
</html>