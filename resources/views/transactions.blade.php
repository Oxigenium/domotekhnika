<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transactions</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous">

</head>
<body>

    <div class="container">
        <h1 class="text-center mb-4">Transactions</h1>
        <div class="row">
            <div class="col-4">
                <ul class="filters list-group">
                    @foreach ($filters as $year => $yearFilter)
                        <a href="/filter/year/{{ $year }}"><li class="list-group-item list-group-item-action{{(isset($currentFilter) && strrpos($currentFilter, 'month/') === false && strrpos($currentFilter, 'year/') !== false) ? (explode('year/',$currentFilter)[1] == $yearFilter['year']?' active':''):''}}">{{ $year }} ({{$yearFilter['count']}})</li></a>
                        @foreach ($yearFilter['months'] as $monthFilter)
                            <a href="/filter/year/{{ $year }}/month/{{$monthFilter->month}}"><li class="list-group-item list-group-item-action{{(isset($currentFilter) && strrpos($currentFilter, 'month/') !== false) ? (explode('month/',$currentFilter)[1] == $monthFilter->month?' active':''):''}}">{{DateTime::createFromFormat('!m', $monthFilter->month)->format('F') }} ({{$monthFilter->count}})</li></a>
                        @endforeach
                            
                    @endforeach
                </ul>
                <button type="button" id="openModal" class="btn btn-primary mt-4 text-white" data-toggle="modal" data-target="#exampleModal">Особенности входных данных</button>
                @if (isset($currentFilter) && isset($page))
                    <a class="btn btn-primary mt-4 text-white" href="/updatedb">Преобразовать возвраты</a>
                @endif
            </div>
            <div class="col-8 ">
                @if (isset($currentFilter) && isset($page))
                    @section('pagination')
                        <nav>
                          <ul class="pagination justify-content-center">
                            <li class="page-item{{$page == 1 ? ' disabled' : ''}}"><a class="page-link" href="{{$currentFilter . '/page/' . max($page-1,1)}}">Previous</a></li>
                            @for ($i = max(1,$page-6); $i < min(ceil($count / 100),$page+6) + 1; $i++)
                                <li class="page-item{{$page == $i ? ' active' : ''}}"><a class="page-link" href="{{$currentFilter . '/page/' . $i}}">{{ $i }}</a></li>
                            @endfor
                            <li class="page-item{{$page == ceil($count / 100) ? ' disabled' : ''}}"><a class="page-link" href="{{$currentFilter . '/page/' . min($page+1,ceil($count / 100))}}">Next</a></li>
                          </ul>
                        </nav>
                    @show
                    <ul>
                        @foreach ($data as $transaction)
                            <div class="card mb-3 text-white{{$transaction->volume > 0?' bg-success': ' bg-danger'}}">
                              <div class="card-body">
                                <h5 class="card-title text-white">{{$transaction->id}} ->  {{$transaction->service}}</h5>
                                <p class="text-white">Card number: {{$transaction->card_number}}</p>
                                <p class="card-text">Volume: {{$transaction->volume}}, Address id: {{$transaction->address_id}}</p>
                                <p class="text-white">{{(new DateTime($transaction->date))->format('Y-m-d H:i:s') }}</p>
                              </div>
                            </div>
                        @endforeach
                    </ul>

                    @yield('pagination')
                    
                @endif
            </div>  
        </div>
    </div>

    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Особенности входных данных</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <pre>
При анализе входных данных были обнаружены следующие аномалии:
1)у топливных карт: 
7824861010044000605
7824861010041093223
7824861010044000621
7824861010044000597
7824861010044000589
7824861010044000613

были транзакции вида:
(29407, '7824861010041093223', '2015-08-04 20:10:00', 82.96, 'Дизельное топливо', 1075);
с положительным значением volume больших размеров и отсутствием записей с отрицательным volume. 
Сделано допущение, что знак неправилен, во входных данных изменён.

2)Найдено 10 записей, у которых значение volume равнялось нулю, 
были проигнорированы и оставлены без изменений.

3)Для расходных транзакций 23575, 25353 соответствующие им возвратные транзакции
имеют большее абсолютное значение volume
    </pre>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>
    <script type="text/javascript">
    $(document).ready(function(){
        $('#openModal').click(function () {
            $('.modal').modal('show');
        })
    });
    </script>
</body>
</html>