<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * HTML output for the payments admin page
 */
?>

<div class="wrap">

    <h1 class="wp-heading-inline">
        <?php echo esc_html( $this->page_title ); ?>
        <a href="https://www.cozmoslabs.com/docs/paid-member-subscriptions/member-payments/?utm_source=wpbackend&utm_medium=pms-documentation&utm_campaign=PMSDocs" target="_blank" data-code="f223" class="pms-docs-link dashicons dashicons-editor-help"></a>
        <a href="<?php echo esc_url( add_query_arg( array( 'page' => $this->menu_slug, 'pms-action' => 'add_payment' ), admin_url( 'admin.php' ) ) ); ?>" class="add-new-h2 page-title-action"><?php echo esc_html__( 'Add New', 'paid-member-subscriptions' ); ?></a>
    </h1>

    <form method="get">
        <input type="hidden" name="page" value="pms-payments-page" />
    <?php

        $this->list_table->prepare_items();
        $this->list_table->views();
        $this->list_table->search_box( esc_html__( 'Search Payments', 'paid-member-subscriptions' ),'pms_search_payments' );
        $this->list_table->display();

    ?>
    </form>

</div>
