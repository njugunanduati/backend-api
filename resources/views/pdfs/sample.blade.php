<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Current History</title>
    <style>
        
        .headingcontainer{
            display: flex;
            background-color: #fff;
            margin: 1rem 0;
            padding: 0 0.525rem;
            flex-direction: column;
            width: 100%;
            max-width: 100%;
            font-family: Avenir, Helvetica, sans-serif;
            box-sizing: border-box;
        }

        .mainheading{
            text-transform: uppercase;
            margin-bottom: 1%;
            font-size: 0.9rem;
            color: #0e123f;
        }


        .notecontainer{
            display: flex;
            background-color: #fff;
            margin-top: 1rem;
            flex-direction: column;
            width: 100%;
            max-width: 100%;
            font-family: Avenir, Helvetica, sans-serif;
            box-sizing: border-box;
        }

        .badge-info {
            color: #fff;
            background-color: #17a2b8;
        }

        .badge-pill {
            padding-right: 0.6em;
            padding-left: 0.6em;
            border-radius: 10rem;
        }

        .badge {
            display: inline-block;
            padding: 0.25em 0.4em;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }

        .box{
            display: flex;
            background-color: #fff;
            width: 100%;
            min-height: 6.25rem;
            flex-direction: column;
            padding: 0 0.525rem;
        }

        .heading{
            text-transform: uppercase;
            margin-bottom: 1%;
            font-size: 0.75rem;
            color: #fff;
            padding: 0.525rem;
            background-color: #0e123f;
            width: 100%;
        }

        .heading span{
            text-transform: none;
        }

        .heading span.right{
            float: right;
        }

        .datebox{
            margin: 0;
            width: 100%;
            padding: 0.525rem 0;
            padding-top: 0;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline;
        }

        .datebox span{
            font-size: 0.56rem;
        }

        .datebox span.right{
            float: right;
        }

        .content{
            display: flex;
            background-color: #f3f3f5;
            width: 100%;
            color: #000;
            padding: 0.525rem;
            border-radius: 2.5px;
            font-size: 0.75rem;
            flex-direction: column;
            font-family: Avenir, Helvetica, sans-serif;
        }

        .content div{
            width: 100%;
        }

        .content span{
            padding-left: 0.4rem;
            color: #868686;
        }

        .content span.value{
            color: #000;
        }

        .content span.label{
            margin-right: 0.6rem;
            font-weight: 600;
            min-width: 7.375rem;
            padding-left: 0;
        }

        .content section{
            display: flex;
        }

        .content div span{
            font-weight: 600;
        }

        #footer {
            position: fixed;
            left: 0;
            right: 0;
            color: #aaa;
            font-size: 0.75em;
        }
        #footer {
            bottom: 0;
            border-top: 0.1pt solid #aaa;
        }
        .page-number:before {
            content: "Page " counter(page);
        }
    </style>

  </head>
  <body>

    <div id="footer">
        <div class="page-number"></div>
    </div>

    <section class="headingcontainer">
        <h6 class="mainheading">{{ $title ?? '' }}</h6>
    </section>
    
    <section class="notecontainer">
        @foreach ($notes as $note)
        <section class="box"> 
            <section class="heading">Notes <span class="badge badge-info badge-pill right">{{Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $note->meeting_time)->toFormattedDateString()}}</span></section>
            <h6 class="datebox"><span class="badge badge-info badge-pill">Next meeting date: {{Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $note->next_meeting_time)->toDayDateTimeString()}}</span></h6>
            
            <section class="content">{{$note->notes}}</section>
            @if (count($note->others) > 0)
                @foreach ($note->others as $other)
                    <section class="content"><b>{{$other->settings->label}}</b> {{$other->note}}</section>
                @endforeach
             @endif
        </section>
            
        @if (count($note->metrics) > 0)
        <section class="box">
            <section class="heading">Metrics</section>
            <section class="content">
                @foreach ($note->metrics as $metric)
                    <div>{{$metric->settings->label}} {{$metric->value}}</div>
                @endforeach
            </section>
        </section>
        @endif

        @if (count($note->tasks) > 0)
        <section class="box">
            <section class="heading">Tasks</section>
                <section class="content">
                    @foreach ($note->tasks as $task)
                        <div>{{ $loop->index +1 }}. {{$task->note}}</div>
                    @endforeach
                </section>
        </section>
        @endif

        @if ($note->reminder)
        <section class="box">
            <section class="heading">Follow up Schedule</section>
            <section class="content">
                <div><span>Reminder Date: </span> {{Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $note->reminder->reminder_date)->toFormattedDateString()}} <span>Reminder Time: </span> {{$note->reminder->reminder_time}} {{$note->reminder->time_zone}}</div>
                <div>{{$note->reminder->note}}</div> 
            </section>
        </section>
        @endif

        @endforeach
    </section>
  </body>
</html>