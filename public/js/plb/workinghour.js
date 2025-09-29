function updateTables(selected_weeks) {

    var weeks;
    if (selected_weeks) {
        weeks = selected_weeks;
    } else if ($("#this_number_of_weeks").val()) {
        weeks = $("#this_number_of_weeks").val();
    }  else {
        weeks = $("#number_of_weeks").val();
    }
    $('#select_number_of_weeks').val(weeks);

    $.ajax({
        url: url('ajax/workinghour-tables'),
        data: {weeks: weeks, perso_id: $("#perso_id").val(), ph_id: $("#id").val()},
        dataType: "html",
        type: "get",
        async: false,
        success: function(result) {
            $("#workinghour_tables").html(result);
        },
        error: function(result) {
        }
  });
  plHebdoCalculHeures2();
  plHebdoMemePlanning();
}

function update_validation_statuses() {

    perso_ids = [];
    $('.perso_ids_li').each(function() {
        perso_ids.push($(this).data('id'));
    });

    // Agent with no right.
    // So only the logged in
    // is in the form.
    if (perso_ids.length == 0) {
        perso_ids.push($('input[name="valide"]').val());
    }

    const agent_id = $('input[name="perso_id"]').val() || perso_ids[0];

    $.ajax({
        url: url('workinghour-statuses'),
           data: { ids: perso_ids, module: 'workinghour', id: agent_id },
           dataType: 'html',
           success: function(result){
               $("#validation-statuses").html(result);

               $('tr#validation').effect("highlight",null,2000);
           },
           error: function(xhr, ajaxOptions, thrownError) {
               information("Une erreur s'est produite lors de la mise à jour de la liste des statuts");
           }
    });
}

$(function(){
    $("document").ready(function(){
        updateTables();
    });

    $("#perso_id").change(function() {
        // Don't reload the form when
        // changing the agent on copy mode.
        if ($('input[name="copy"]').val()) {
            updateTables();
            return false;
        }
        const queryString = window.location.search;
        document.location.href="/workinghour/add/" + this.value + queryString;
    });

    $("#select_number_of_weeks").change(function() {
        updateTables(this.value);
    });

    update_validation_statuses();

});
