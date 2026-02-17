<li class="nav-item">
    <a class="nav-link" id="globinary-awb-tab" data-toggle="tab" href="#globinary-awb-content" role="tab" aria-controls="globinary-awb-content" aria-selected="false">
        <i class="material-icons">local_shipping</i>
        {l s='GLOBINARY AWB România' mod='globinaryawbtracking'}
        {if isset($awb_number) && $awb_number}
            <span class="badge badge-success">{$awb_number}</span>
        {/if}
    </a>
</li>
