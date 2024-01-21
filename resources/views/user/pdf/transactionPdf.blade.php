<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

    <h2>Transaction Receipt</h2>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Transaction Number</th>
                <th>Account No</th>
                <th>Account Name</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $data)
            <tr>
                <td data-label="{{ __('Date') }}">{{ $data->created_at->toFormattedDateString() }}</td>
                <td data-label="{{ __('Transaction Number') }}">{{ $data->transaction_no }}</td>
                @if ($data->receiver_id)
                  @php
                    $receiver = App\Models\User::whereId($data->receiver_id)->first();
                  @endphp

                  <td data-label="{{ __('Account No') }}">{{ $receiver != NULL ? $receiver->account_number : 'User Deleted' }}</td>
                  <td data-label="{{ __('Account Name') }}">{{ $receiver != NULL ? $receiver->name : 'User Deleted' }}</td>
                @endif

                @if (!$data->receiver_id)
                  @php
                    $beneficiary = App\Models\Beneficiary::whereId($data->beneficiary_id)->first();
                  @endphp
                  <td data-label="{{ __('Account No') }}">{{ $beneficiary != NULL ? $beneficiary->account_number : 'deleted' }}</td>
                  <td data-label="{{ __('Account Name') }}">{{ $beneficiary != NULL ? $beneficiary->account_name : 'deleted' }}</td>
                @endif
                <td data-label="{{ __('Type') }}">{{ $data->type }} {{ __('Bank') }}</td>
                <td data-label="{{ __('Amount') }}">{{ showNameAmount($data->amount) }}</td>
                <td data-label="{{ __('Status') }}">
                  @if ($data->status == 1)
                    <span class="badge bg-success">{{ __('Completed')}}</span>
                  @elseif($data->status == 2)
                    <span class="badge bg-danger">{{ __('Rejected')}}</span>
                  @else
                    <span class="badge bg-warning">{{ __('Pending')}}</span>
                  @endif
                </td>



            </tr>
        @endforeach
        </tbody>
    </table>

</body>
</html>


