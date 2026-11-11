@props(['imports'])
<section class="integration-card integration-history"><div class="integration-card-heading"><div><h2>Historial de cargas</h2><p>Las ejecuciones conservan su auditoría aunque el archivo temporal expire.</p></div></div>
@if($imports->count())
<div class="integration-history-table"><table><thead><tr><th>Fecha</th><th>Usuario</th><th>Tipo</th><th>Archivo</th><th>Periodo</th><th>Resultado</th><th>Estado</th></tr></thead><tbody>@foreach($imports as $item)<tr><td>{{ optional($item->created_at)->format('d/m/Y H:i') }}</td><td>{{ $item->usuario?->nombre ?? '—' }}</td><td>{{ ucfirst($item->tipo) }}</td><td><a href="{{ route('cargas.importaciones.show', $item) }}">{{ $item->nombre_archivo }}</a></td><td>{{ $item->periodo ?? '—' }}</td><td>{{ $item->insertadas }} nuevos · {{ $item->actualizadas }} act. · {{ $item->fallidas }} err.</td><td><span class="import-status import-status--{{ $item->estado }}">{{ ucfirst(str_replace('_',' ', $item->estado)) }}</span></td></tr>@endforeach</tbody></table></div>
{{ $imports->links() }}
@else <p class="integration-empty">Aún no hay cargas registradas.</p>@endif</section>
