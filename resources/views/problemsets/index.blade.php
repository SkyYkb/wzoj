@extends ('layouts.master')

@section ('title')
problemsets
@endsection

@section ('content')

@foreach ($problemsets as $problemset)
<p>
<a href='/s/{{$problemset->id}}'>{{$problemset->name}}</a>
@can ('update',$problemset)
<a href='/s/{{$problemset->id}}/edit'>edit</a>
@endcan
</p>
@endforeach

@can ('create',App\Problemset::class)
<form method='POST'>
{{csrf_field()}}
<button>new problemset</button>
</form>
@endcan

@endsection
