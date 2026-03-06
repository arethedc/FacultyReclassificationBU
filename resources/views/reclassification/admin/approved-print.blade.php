<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Approved Reclassifications Print</title>
    <style>
        :root { color-scheme: light; }
        body {
            font-family: "Times New Roman", Georgia, serif;
            margin: 24px;
            color: #111827;
            background: #ffffff;
        }
        .controls {
            margin-bottom: 16px;
            display: flex;
            gap: 8px;
        }
        .btn {
            border: 1px solid #d1d5db;
            background: #ffffff;
            color: #111827;
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            cursor: pointer;
        }
        .paper {
            max-width: 980px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 14px;
        }
        .header .line-1 {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .header .line-2 {
            font-size: 14px;
            margin-top: 2px;
        }
        .title {
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            margin: 10px 0 2px;
        }
        .subtitle {
            text-align: center;
            font-size: 14px;
            margin-bottom: 18px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        th, td {
            border: 1px solid #1f2937;
            padding: 8px;
            vertical-align: top;
        }
        th {
            text-align: left;
            background: #f3f4f6;
        }
        .text-center { text-align: center; }

        @media print {
            body {
                margin: 12mm;
            }
            .controls {
                display: none !important;
            }
            .paper {
                max-width: none;
            }
        }
    </style>
</head>
<body>
@php
    $periodName = $period?->name ?? 'No Active Period';
    $periodCycle = trim((string) ($period?->cycle_year ?? '')) !== '' ? $period->cycle_year : 'N/A';
@endphp

<div class="controls">
    <a class="btn" href="{{ url()->previous() }}">Back</a>
    <button class="btn" type="button" onclick="window.print()">Print</button>
</div>

<div class="paper">
    <div class="header">
        <div class="line-1">Baliuag University</div>
        <div class="line-2">Baliwag, Bulacan</div>
    </div>

    <div class="title">Approved Reclassifications</div>
    <div class="subtitle">Period: {{ $periodName }} ({{ $periodCycle }})</div>

    <table>
        <thead>
            <tr>
                <th style="width: 28%;">Name</th>
                <th style="width: 24%;">Department</th>
                <th style="width: 24%;">Current Rank</th>
                <th style="width: 24%;">Approved Rank</th>
            </tr>
        </thead>
        <tbody>
            @forelse($applications as $app)
                @php
                    $profile = $app->faculty?->facultyProfile;
                    $fallbackCurrentRank = $profile?->rankLevel?->title
                        ?: trim((string) (($profile?->teaching_rank ?? '') . (($profile?->rank_step ?? '') !== '' ? ' - ' . $profile->rank_step : '')));
                    $currentRank = $app->current_rank_label_at_approval ?: ($fallbackCurrentRank ?: '-');
                    $approvedRank = $app->approved_rank_label ?: $currentRank;
                @endphp
                <tr>
                    <td>{{ $app->faculty?->name ?? 'Faculty' }}</td>
                    <td>{{ $app->faculty?->department?->name ?? '-' }}</td>
                    <td>{{ $currentRank }}</td>
                    <td>{{ $approvedRank }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">No approved records found for this period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
</body>
</html>
