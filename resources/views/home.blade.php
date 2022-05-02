@extends('layouts.app')

@section('content')
<div class="container">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">status</th>
                <th scope="col">output</th>
                <th scope="col">exception</th>
                <th scope="col">action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($jobs as $job)
                <tr>
                    <th>{{ $job->id }}</th>
                    <th>{{ $job->status }}</th>
                    <th class="overflow-auto"">
                        <pre>
                        @php(print_r($job->output))
                        </pre>
                    </th>
                    <th>
                        @php(print_r($job->exception))
                    </th>
                    <th>
                        <a href="{{ route('job.delete', $job->id) }}" class="btn btn-danger">Delete</a>
                    </th>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
