jQuery(document).ready(function($){
    $('.eos-scfm-suggest-page').suggest(scfm.ajax_url + "?action=eos_scfm_suggest_page&post_type=" + scfm.post_type + '&id=' + scfm.id,{
      multiple:false,
      onSelect: function(){
        var results = $('.ac_results'),id = $(results).find('.ac_over .scfm-page').attr('data-id');
        $('.eos-scfm-suggest-page-id').val(id);
      }
    });
});
