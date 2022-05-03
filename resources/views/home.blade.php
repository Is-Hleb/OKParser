@extends('layouts.app')

@section('content')
<div class="container">
    <table class="table table-bordered">
        <thead class="thead-dark">
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
                    <th scope="row" class="fw-bold d-flex justify-content-center">{{ $job->id }}</th>
                    <th>{{ $job->status }}</th>
                    <th class="overflow-auto"">
                        @php(dump($job->output))
                    </th>
                    <th>
                        @php(dump($job->exception))
                    </th>
                    <th class="d-flex justify-content-center">
                        <a href="{{ route('job.delete', $job->id) }}" class="btn btn-danger my-auto">Delete</a>
                    </th>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
