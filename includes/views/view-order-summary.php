<?php
/**
 * HTML output for the front-end Order Summary
 */

?>

<ul class="pms-field-section pms-price-breakdown__holder">

    <li class="pms-field pms-field-type-heading">
        <h3><?php echo esc_html( $args['heading'] ); ?></h3>
    </li>

    <?php do_action( 'pms_order_summary_before' ); ?>

    <?php // legacy hook kept for backwards compatibility with third-party add-ons ?>
    <?php do_action( 'pms_tax_price_breakdown_before' ); ?>

    <div class="pms-price-breakdown">

        <table>
            <tbody class="pms-order-summary__items"></tbody>

            <tbody class="pms-order-summary__totals">
                <tr class="pms-order-summary__subtotal-row">
                    <td class="pms-label pms-subtotal__label"><?php esc_html_e( 'Subtotal:', 'paid-member-subscriptions' ); ?></td>
                    <td class="pms-value pms-subtotal__value"></td>
                </tr>

                <tr class="pms-order-summary__tax-row">
                    <td class="pms-label pms-tax__label"><?php esc_html_e( 'VAT/Tax:', 'paid-member-subscriptions' ); ?></td>
                    <td class="pms-value pms-tax__value"></td>
                </tr>

                <tr class="pms-order-summary__total-row">
                    <td class="pms-label pms-total__label"><?php esc_html_e( 'Total Price:', 'paid-member-subscriptions' ); ?></td>
                    <td class="pms-value pms-total__value"></td>
                </tr>
            </tbody>
        </table>

    </div>

    <?php // legacy hook kept for backwards compatibility with third-party add-ons ?>
    <?php do_action( 'pms_tax_price_breakdown_after' ); ?>

    <?php do_action( 'pms_order_summary_after' ); ?>

</ul>
