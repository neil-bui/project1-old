<?php
  global $wpdb;

  $wpdb->capture_users = $wpdb->prefix . 'capture_users';
  $captured_users = $wpdb->get_results( "SELECT * FROM $wpdb->capture_users" );

  //echo '<pre>'; print_r( $captured_users ); echo '</pre>';
?>

<div class="wrap">
  <h1>Users Information Before Checkout</h1>
  <p></p>
  <table id="users_table" class='stripe row-border hover' cellspacing="0" width="100%">
    <thead>
      <tr>
        <th>ID</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Created On</th>
      </tr>
    </thead>
    <tfoot>
      <tr>
        <th>ID</th>
        <th>Full Name</th>
        <th>Email</th>
        <th>Created On</th>
      </tr>
    </tfoot>
    <tbody>
      <?php if( count( $captured_users ) > 0 ) : $count = 1; ?>
        <?php foreach( $captured_users as $cp_user ) : ?>
          <tr>
            <td><?php echo $count++; ?></td>
            <td><?php echo $cp_user->fullname; ?></td>
						<td><?php echo $cp_user->email; ?></td>
            <td><?php echo mysql2date( 'dS M,Y h:i A', $cp_user->date_captured ); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>