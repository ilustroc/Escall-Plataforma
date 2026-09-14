<?php
namespace App\Services;
use App\Models\Cliente;
use Illuminate\Pagination\LengthAwarePaginator;
class ClientService {
    public function query(array $filters) {
        return Cliente::query()->when($filters['dni']??null,fn($q,$v)=>$q->where('dni','like','%'.$v.'%'))->when($filters['codigo']??null,fn($q,$v)=>$q->where('codigo','like','%'.$v.'%'))->when($filters['titular']??null,fn($q,$v)=>$q->where('titular','like','%'.$v.'%'))->when($filters['cartera']??null,fn($q,$v)=>$q->where('cartera',$v));
    }
    public function grouped(array $filters): LengthAwarePaginator {
        $query=$this->query($filters);
        if (!array_filter(array_intersect_key($filters,array_flip(['dni','codigo','titular','cartera'])),fn($v)=>$v!==null && $v!=='')) $query->whereRaw('1=0');
        $groups=$query->select('dni')->distinct()->orderBy('dni')->paginate(15)->withQueryString();
        $rows=$this->query($filters)->whereIn('dni',$groups->getCollection()->pluck('dni'))->orderBy('codigo')->get(['id','dni','titular','codigo','cartera'])->groupBy('dni');
        $groups->setCollection($groups->getCollection()->map(function($item) use ($rows) { $accounts=$rows[$item->dni]; return ['dni'=>$item->dni,'titular'=>$accounts->first()->titular,'codigos'=>$accounts->pluck('codigo'),'carteras'=>$accounts->pluck('cartera')->unique()->values(),'ids'=>$accounts->pluck('id')]; }));
        return $groups;
    }
}
