@extends('app')

@section('content')
    <h1>All Applications</h1>
    <hr>
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Name</th>
                <th>Applicant</th>
                <th>Status</th>
                <th>Judge Status</th>
                <th>Score</th>
                <th>Created</th>
                <th>Last Modified</th>
            </tr>
        </thead>

        <tbody>
            @foreach($applications as $application)
                <tr>
                    <td>
                        @if($application->status == 'new')
                            <a href="/applications/{{ $application->id }}">{{ $application->name }}</a>
                        @else
                            <a href="/applications/{{ $application->id }}/review">{{ $application->name }}</a>
                        @endif
                    </td>
                    <td>{{ $application->user->name }}</td>
                    <td>{{ $application->status }}</td>
                    <td>{{ $application->judge_status }}</td>
                    <td>{{ $application->objective_score }} / {{ $application->subjective_score }} / {{ $application->total_score }}</td>
                    <td>{{ $application->created_at->format('Y-m-d H:i:s e') }}</td>
                    <td>{{ $application->updated_at->format('Y-m-d H:i:s e') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

@endsection
