<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Get configuration settings for the custom payment gateway.
 *
 * @return array
 */
function slsmanual_config()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Custom Payment Gateway',
        ],
        'paymentWallet' => [
            'FriendlyName' => 'Payment Wallet',
            'Type' => 'text',
            'Size' => '40'
        ],
        'instruction' => [
            'FriendlyName' => 'Instruction',
            'Type' => 'textarea'
        ]
    ];
}

/**
 * Generate payment details display.
 *
 * @param array $params WHMCS parameters
 * @return string HTML markup
 */
function slsmanual_link($params)
{
    $response = slsmanual_handlePost($params);
    $name = $params['name'];
    $paymentWallet = $params['paymentWallet'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
    $instruction = $params['instruction'];
    $notes = trim(slsmanual_getNote($params['invoiceid']));

    $markup = <<<HTML
<div class="panel panel-default" style="margin-top: 10px;">
    <div style="padding-top:10px;">
        <h3 class="panel-title"><strong>Payment Details</strong></h3>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-bordered" style="margin-bottom: 10px;">
                <tbody>
                    <tr>
                        <td><b>Gateway:</b></td>
                        <td class="text-center">$name</td>
                    </tr>
                    <tr>
                        <td><b>Wallet:</b></td>
                        <td class="text-center">$paymentWallet</td>
                    </tr>
                    <tr>
                        <td><b>Amount:</b></td>
                        <td class="text-center">$amount</td>
                    </tr>
                    <tr>
                        <td><b>Reference:</b></td>
                        <td class="text-center">$invoiceId</td>
                    </tr>
                </tbody>
            </table>

            <p>$instruction</p>
            <form method="post" action="#" class="form-inline">
                <input type="text" name="trx_id" value="$notes" class="form-control" placeholder="Your PayPal Address...">
                <input type="submit" value="Submit" name="submit" class="btn btn-primary">
                $response
            </form>
        </div>
    </div>
</div>
HTML;

    return $markup;
}

/**
 * Handle form submission for transaction ID.
 *
 * @param array $params WHMCS parameters
 * @return string Success message
 */
function slsmanual_handlePost($params)
{
    if (isset($_REQUEST['trx_id'])) {
        slsmanual_updateNote($params['invoiceid'], $_REQUEST['trx_id']);
        createSupportTicket($params['invoiceid'], $_REQUEST['trx_id']);
        return '<p style="margin-top: 5px;color: green;">Your transaction info submitted for review. A support ticket has been created.</p>';
    }

    return '';
}

/**
 * Get transaction notes for an invoice.
 *
 * @param int $invoiceId Invoice ID
 * @return string Transaction notes
 */
function slsmanual_getNote($invoiceId)
{
    $invoice = localAPI('GetInvoice', ['invoiceid' => $invoiceId]);
    return @str_replace('TRX ID:', '', $invoice['notes']);
}

/**
 * Update transaction notes for an invoice.
 *
 * @param int $invoiceId Invoice ID
 * @param string $note Transaction ID
 */
function slsmanual_updateNote($invoiceId, $note)
{
    $modifiedNote = 'TRX ID: ' . $note;
    localAPI('UpdateInvoice', ['invoiceid' => $invoiceId, 'notes' => $modifiedNote]);
}

/**
 * Create a support ticket.
 *
 * @param int $invoiceId Invoice ID
 * @param string $trxId Transaction ID
 */
function createSupportTicket($invoiceId, $trxId)
{
    $ticketData = [
        'deptid' => 1, // You may need to adjust the department ID based on your setup
        'subject' => 'Payment Issue for Invoice #' . $invoiceId,
        'message' => 'This is an automated ticket by system, Check the transaction and active my service my PayPal Email is: ' . $trxId,
        'clientid' => getClientIDFromInvoice($invoiceId),
        'status' => 'Open',
        'priority' => 'Medium',
    ];

    localAPI('OpenTicket', $ticketData);
}

/**
 * Get the client ID associated with an invoice.
 *
 * @param int $invoiceId Invoice ID
 * @return int Client ID
 */
function getClientIDFromInvoice($invoiceId)
{
    $invoice = localAPI('GetInvoice', ['invoiceid' => $invoiceId]);
    return $invoice['userid'];
}
