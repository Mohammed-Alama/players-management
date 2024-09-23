(function ($, Drupal, once) {
  Drupal.behaviors.players = {
    attach: function (context, settings) {
      once('players', '.field--name-field-club', context).forEach(function (element) {
        const $wrapper = $(element);

        function updateCurrentClubCheckboxes() {
          const $checkboxes = $wrapper.find('.field--name-field-current-club input[type="checkbox"]');
          const $checkedBoxes = $checkboxes.filter(':checked');

          if ($checkedBoxes.length > 0) {
            $checkboxes.not(':checked').prop('disabled', true);
          } else {
            $checkboxes.prop('disabled', false);
          }
        }

        function updateClubAutocomplete() {
          const $federationInput = $('input[name="field_federation[0][target_id]"]');
          const federationValue = $federationInput.val();
          const federationId = federationValue.match(/\((\d+)\)$/);

          if (federationId && federationId[1]) {
            $wrapper.find('.field--name-field-club input[data-autocomplete-path]').each(function() {
              const $input = $(this);
              const newPath = `/players/club-autocomplete/${federationId[1]}`;
              const currentPath = $input.attr('data-autocomplete-path');

              if (currentPath !== newPath) {
                $input.attr('data-autocomplete-path', newPath);

                // Only clear the value if it doesn't match the new federation
                const currentValue = $input.val();
                const currentClubId = currentValue.match(/\((\d+)\)$/);
                if (currentClubId && currentClubId[1]) {
                  // Check if the current club belongs to the new federation
                  checkClubFederation(currentClubId[1], federationId[1], function(belongs) {
                    if (!belongs) {
                      $input.val('');
                    }
                  });
                }
              }
            });
          }
        }

        function checkClubFederation(clubId, federationId, callback) {
          // Make an AJAX call to check if the club belongs to the federation
          $.get(`/players/check-club-federation/${clubId}/${federationId}`, function(response) {
            callback(response.belongs);
          });
        }

        // Use the 'change' event for the federation autocomplete input
        $('input[name="field_federation[0][target_id]"]').on('change', updateClubAutocomplete);

        // Initial call to set up club autocomplete
        updateClubAutocomplete();

        $wrapper.on('change', '.field--name-field-current-club input[type="checkbox"]', function () {
          updateCurrentClubCheckboxes();
        });

        updateCurrentClubCheckboxes();

        $wrapper.on('mouseup', '.field-add-more-submit', function () {
          setTimeout(updateCurrentClubCheckboxes, 500);
        });
      });
    }
  };
})(jQuery, Drupal, once);
