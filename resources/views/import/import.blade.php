<!DOCTYPE html>
<html lang="en">
<head>
  <title>Laravel 6 Import Export Excel & Csv file example tutorial - XpertPhp</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container">
 <form action="{{ url('customer/import/today') }}" method="post" name="importform" enctype="multipart/form-data">
 @csrf
 <div class="form-group">
 <label for="file">File:</label>
 <input type="file" name="import_file" class="form-control">
 </div>
 {{-- <div class="form-group">
 <a class="btn btn-info" href="{{ url('export') }}">Export File</a>
 </div> --}}
 <button class="btn btn-success">Import File</button>
 </form>
</div>
</body>
</html>
