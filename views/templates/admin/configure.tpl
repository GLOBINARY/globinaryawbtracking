<div class="panel">
    <h3><i class="icon icon-truck"></i> {l s='GLOBINARY AWB România' mod='globinaryawbtracking'}</h3>
    <p>
        {l s='Configurează credențialele DPD, Sameday, DSC pentru emiterea AWB-urilor și urmărirea coletelor.' mod='globinaryawbtracking'}
    </p>
<div class="alert alert-warning">
    {l s='Maparea statusurilor este opțională și se folosește doar pentru actualizarea automată a stărilor comenzilor.' mod='globinaryawbtracking'}
</div>
<div class="alert alert-info">
    {l s='Maparea este per status/cod de operațiune și este salvată în configurația PrestaShop. DPD, Sameday, Dragon Star au mapări separate.' mod='globinaryawbtracking'}
</div>

</div>


<div class="panel">
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item active">
            <a class="nav-link active" href="#globinary-dpd" data-toggle="tab" role="tab">DPD</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#globinary-sameday" data-toggle="tab" role="tab">Sameday</a>
        </li>
    </ul>
    <div class="tab-content" style="padding-top: 15px;">
        <div class="tab-pane active" id="globinary-dpd" role="tabpanel">
{if !$dpd_import_ok}
    <div class="alert alert-warning">
        {l s='Importul localităților DPD nu este finalizat. Te rugăm folosește butonul de sincronizare de mai jos.' mod='globinaryawbtracking'}
        {if $dpd_import_last_error}<br><small>{$dpd_import_last_error|escape:'htmlall':'UTF-8'}</small>{/if}
    </div>
{/if}

<div class="panel">
    <h3><i class="icon icon-refresh"></i> {l s='Sincronizare localități DPD' mod='globinaryawbtracking'}</h3>
    <p>
        {l s='Ultima sincronizare:' mod='globinaryawbtracking'}
        {if $dpd_last_sync}{$dpd_last_sync}{else}{l s='Niciodată' mod='globinaryawbtracking'}{/if}
    </p>
    <form method="post">
        <button type="submit" name="submitGlobinaryAwbSyncDpdSites" class="btn btn-primary">
            {l s='Sincronizează acum' mod='globinaryawbtracking'}
        </button>
    </form>
</div>
        </div>
        <div class="tab-pane" id="globinary-sameday" role="tabpanel">
{if !$sameday_import_ok}
    <div class="alert alert-warning">
        {l s='Importul localităților Sameday nu este finalizat. Te rugăm folosește butonul de sincronizare de mai jos.' mod='globinaryawbtracking'}
        {if $sameday_import_last_error}<br><small>{$sameday_import_last_error|escape:'htmlall':'UTF-8'}</small>{/if}
    </div>
{/if}

<div class="panel">
    <h3><i class="icon icon-refresh"></i> {l s='Sincronizare localități Sameday' mod='globinaryawbtracking'}</h3>
    <p>
        {l s='Ultima sincronizare:' mod='globinaryawbtracking'}
        {if $sameday_last_sync}{$sameday_last_sync}{else}{l s='Niciodată' mod='globinaryawbtracking'}{/if}
    </p>
    <form method="post">
        <button type="submit" name="submitGlobinaryAwbSyncSamedaySites" class="btn btn-primary">
            {l s='Sincronizează acum' mod='globinaryawbtracking'}
        </button>
    </form>
</div>
        </div>
    </div>
</div>
