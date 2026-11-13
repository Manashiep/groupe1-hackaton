@extends('layouts.admin')

@section('title', 'Quartiers & zones de collecte')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0 small">{{ $quartiers->count() }} quartiers et villages couverts par le service.</p>
    <a href="{{ route('admin.quartiers.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Ajouter un quartier</a>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr><th>Quartier</th><th>Ville · Île</th><th class="text-end">Habitants inscrits</th><th class="text-end">Passages / sem.</th><th class="text-end">Problèmes ouverts</th><th style="min-width: 220px">Indice de propreté</th><th></th></tr>
            </thead>
            <tbody>
                @foreach ($quartiers as $row)
                    @php($q = $row['quartier'])
                    <tr>
                        <td class="fw-semibold"><a href="{{ route('quartiers.show', $q) }}" class="text-body text-decoration-none">{{ $q->name }}</a></td>
                        <td class="small">{{ $q->ville }} · {{ $q->ile }}</td>
                        <td class="text-end tabular">{{ $q->residents_count }}</td>
                        <td class="text-end tabular">{{ $q->schedules_count }}</td>
                        <td class="text-end tabular">{{ $q->open_reports }}</td>
                        <td><x-score :data="$row" /></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.quartiers.edit', $q) }}" class="btn btn-soft btn-sm">Modifier</a>
                            <form method="POST" action="{{ route('admin.quartiers.destroy', $q) }}" class="d-inline" onsubmit="return confirm('Supprimer ce quartier ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-soft btn-sm text-danger" aria-label="Supprimer"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
