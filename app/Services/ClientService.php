<?php
namespace App\Services;
use App\Models\Cliente;
use Illuminate\Pagination\LengthAwarePaginator;
class ClientService {
    public function query(array $filters) {
        $documento = $filters['documento'] ?? $filters['dni'] ?? null;
        $nombre = $filters['nombre'] ?? $filters['titular'] ?? null;
        return CarteraMensualQuery::vigentes()->when($documento,fn($q,$v)=>$q->where('documento','like','%'.$v.'%'))->when($filters['codigo']??null,fn($q,$v)=>$q->where('codigo','like','%'.$v.'%'))->when($nombre,fn($q,$v)=>$q->where('nombre','like','%'.$v.'%'))->when($filters['cartera']??null,fn($q,$v)=>$q->where('cartera',$v));
    }
    public function grouped(array $filters): LengthAwarePaginator {
        $query=$this->query($filters);
        if (!array_filter(array_intersect_key($filters,array_flip(['documento','dni','codigo','nombre','titular','cartera'])),fn($v)=>$v!==null && $v!=='')) $query->whereRaw('1=0');
        $groups=$query->select('documento')->distinct()->orderBy('documento')->paginate(15)->withQueryString();
        $rows=$this->query($filters)->whereIn('documento',$groups->getCollection()->pluck('documento'))->orderBy('codigo')->get(['id','documento','nombre','codigo','cartera'])->groupBy('documento');
        $groups->setCollection($groups->getCollection()->map(function($item) use ($rows) { $accounts=$rows[$item->documento]; return ['dni'=>$item->documento,'titular'=>$accounts->first()->nombre,'codigos'=>$accounts->pluck('codigo'),'carteras'=>$accounts->pluck('cartera')->unique()->values(),'ids'=>$accounts->pluck('id')]; }));
        return $groups;
    }
}
