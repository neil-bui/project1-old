jQuery(document).ready(function() {

  jQuery('#users_table').DataTable({
    responsive: true,
    stateSave: true,
    "columnDefs": [
      { className: "dt-body-center", "targets": [ 0, 1, 2, 3 ] }
    ]
  });

});