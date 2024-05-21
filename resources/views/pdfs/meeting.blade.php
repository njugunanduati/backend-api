<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Old Meeting Notes</title>
    <style>
        .headingcontainer {
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

        .mainheading {
            text-transform: uppercase;
            margin-bottom: 1%;
            font-size: 0.9rem;
            color: #0e123f;
            border-bottom: 0.1pt solid #aaa;
        }


        .notescontainer {
            display: flex;
            background-color: #fff;
            margin-top: 1rem;
            flex-direction: column;
            width: 100%;
            max-width: 100%;
            font-family: Avenir, Helvetica, sans-serif;
            box-sizing: border-box;
        }

        .badge-light {
           color: #212529;
            background-color: #f8f9fa;
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

        .box {
            display: flex;
            background-color: #fff;
            width: 100%;
            min-height: 6.25rem;
            flex-direction: column;
            padding: 0 0.525rem;
        }

        .heading {
            text-transform: uppercase;
            margin-bottom: 1%;
            font-size: 0.75rem;
            color: #fff;
            padding: 0.525rem;
            background-color: #0e123f;
            width: 100%;
        }

        .heading.history{
            background-color: #31c0e9 !important;
        }


        .heading span {
            text-transform: none;
        }

        .heading span.right {
            float: right;
        }

        .datebox {
            margin: 0;
            width: 100%;
            padding: 0.525rem 0;
            padding-top: 0;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline;
        }

        .datebox span {
            font-size: 0.56rem;
        }

        .datebox span.right {
            float: right;
        }

        .content {
            display: flex;
            background-color: #f3f3f5;
            width: 100%;
            color: #000;
            padding: 0.525rem;
            border-radius: 2.5px;
            font-size: 0.75rem;
            flex-direction: row;
            font-family: Avenir, Helvetica, sans-serif;
        }

        .content div {
            width: 100%;
        }

        .content span {
            padding-left: 0.4rem;
            color: #868686;
            font-family: Avenir, Helvetica, sans-serif;
        }

        .content span.value {
            color: #000;
        }

        .content span.label {
            margin-right: 0.6rem;
            font-weight: 600;
            min-width: 7.375rem;
            padding-left: 0;
            font-family: Avenir, Helvetica, sans-serif;
        }

        .content section {
            display: flex;
        }

        .content div span {
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
            content: "Page "counter(page);
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
    
    @foreach ($meeting as $note)
    <section class="notescontainer">
        <section class="box">
            <section class="heading history">Meeting Note <span class="badge badge-light badge-pill"style="float: right;">{{$note->meeting_date}}</span></section>
            <h6 class="datebox"><span class="badge badge-info badge-pill">Next meeting date: {{$note->next_meeting_date}}</span></h6>
            <section direction="row" class="content"><span class="label">Title: </span> {{$note->meeting_title}}</section>
            <section direction="row" class="content"><span class="label">Notes: </span> {{$note->meeting_notes}}</section>
            <section direction="row" class="content"><span class="label">Current revenue:</span> {{$note->current_revenue}}</section>
            <section direction="row" class="content"><span class="label">Coach action steps:</span> {{$note->coach_action_steps}}</section>
            <section direction="row" class="content"><span class="label">Client action steps:</span> {{$note->client_action_steps}}</section>
            <section direction="row" class="content"><span class="label">Meeting keywords:</span> {{$note->meeting_keywords}}</section>
        </section>
    </section>
    @endforeach
</body>

</html>