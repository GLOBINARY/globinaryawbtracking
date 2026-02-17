<div class="box">
    <h3>{l s='GLOBINARY AWB România' mod='globinaryawbtracking'}</h3>

    {if $tracking_entries|@count > 0}
        <ul>
            {foreach from=$tracking_entries item=entry}
                <li>
                    <strong>{l s='AWB:' mod='globinaryawbtracking'}</strong> {$entry.awb_number}<br>
                    <strong>{l s='Status:' mod='globinaryawbtracking'}</strong> {$entry.awb_status}<br>
                    <span>
                        <strong>{l s='Link urmărire:' mod='globinaryawbtracking'}</strong>
                        <a href="{$entry.tracking_url}" target="_blank">{$entry.tracking_url}</a>
                    </span>
                </li>
                <hr/>
            {/foreach}
        </ul>
    {else}
        <div class="alert alert-warning">
            {l s='Nu există informații despre curier.' mod='globinaryawbtracking'}
        </div>
    {/if}
</div>
