<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Old Implementation Notes</title>
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

        .box .content {
            display: flex;
            background-color: #f3f3f5;
            width: 100%;
            color: #000;
            padding: 0.4rem;
            border-radius: 2.5px;
            font-size: 0.75rem;
            flex-direction: column !important;
            font-family: Avenir, Helvetica, sans-serif;
        }

        .box .content.row{
            flex-direction: row !important;
        }

        .box .content div {
            width: 100%;
        }

        .box .content span {
            padding-left: 0.4rem;
            color: #868686;
            font-family: Avenir, Helvetica, sans-serif;
        }

        .box .content span.value {
            color: #000;
        }

        .box .content span.label {
            margin-right: 0.6rem;
            font-weight: 600;
            min-width: 7.375rem;
            padding-left: 0;
            font-family: Avenir, Helvetica, sans-serif;
        }

        .box .content section {
            display: flex;
        }

        .box .content div span {
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
    
    @foreach ($implementation as $impl)
    <section class="notescontainer">
        <section class="box">
            <section class="heading history">Implementation Note <span class="badge badge-light badge-pill" style="float: right;">{{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $impl->updated_at)->toFormattedDateString() }}</span></section>
                <section class="content row">
                    <span class="label">Module: </span> Market Dominating Position
                </section>
                
                @if (count($impl->coachingNotes) > 0)
                    <section class="content">
                        <span class="label">Notes: </span>
                        @foreach ($impl->coachingNotes as $no)
                            <span class="value">{{$no->notes}}</span>
                        @endforeach
                    </section>
                @endif

                @if (count($impl->coachingWeeklyRevenue) > 0)
                    <section class="content">
                        <span class="label">Weekly Revenue: </span>
                        @foreach ($impl->coachingWeeklyRevenue as $rev)
                            <span class="value">{{$rev->weekly_revenue}}</span>
                        @endforeach
                    </section>
                @endif

                @if (count($impl->coachingWeeklyLeads) > 0)
                    <section class="content">
                        <span class="label">Weekly Leads: </span>
                        @foreach ($impl->coachingWeeklyLeads as $lea)
                            <span class="value">{{$lea->weekly_leads}}</span>
                        @endforeach
                    </section>
                @endif

                @if (count($impl->coachingWeeklyAppointments) > 0)
                    <section class="content">
                        <span class="label">Weekly Appointments: </span>
                        @foreach ($impl->coachingWeeklyAppointments as $app)
                            <span class="value">{{$app->weekly_appointments}}</span>
                        @endforeach
                    </section>
                @endif

                @if (count($impl->coachingActionsMustComplete) > 0)
                    <section class="content">
                        <span class="label">Actions That Client MUST Complete Before Next Session: </span>
                        @foreach ($impl->coachingActionsMustComplete as $mus)
                            <span class="value">{{$mus->notes}}</span>
                        @endforeach
                    </section>
                @endif

                @if (count($impl->coachingActionsNeedComplete) > 0)
                    <section class="content">
                        <span class="label">Actions They Still Need to Complete: </span>
                        @foreach ($impl->coachingActionsNeedComplete as $nee)
                            <span class="value">{{$nee->notes}}</span>
                        @endforeach
                    </section>
                @endif

                @if (count($impl->coachingBiggestChallenges) > 0)
                    <section class="content">
                        <span class="label">Biggest Challenges of the Past Week: </span>
                        @foreach ($impl->coachingBiggestChallenges as $cha)
                            <span class="value">{{$cha->notes}}</span>
                        @endforeach
                    </section>
                @endif

                @if (count($impl->coachingBiggestWins) > 0)
                    <section class="content">
                        <span class="label">Biggest Wins of the Past Week: </span>
                        @foreach ($impl->coachingBiggestWins as $win)
                            <span class="value">{{$win->notes}}</span>
                        @endforeach
                    </section>
                @endif

                @if (count($impl->coachingCoachesHelp) > 0)
                    <section class="content">
                        <span class="label">As A Coach, How Can I Help You In The Coming Week?: </span>
                        @foreach ($impl->coachingCoachesHelp as $hel)
                            <span class="value">{{$hel->notes}}</span>
                        @endforeach
                    </section>
                @endif

            </section>
    </section>
    @endforeach
</body>

</html>