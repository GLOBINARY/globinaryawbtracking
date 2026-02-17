<div class="container" id="globinary-awb-content">
    <div class="mb-3">
        <strong>{l s='GLOBINARY AWB România' mod='globinaryawbtracking'}</strong><br>
    </div>
    <form id="dpd-courier-form" method="post" class="mb-3">
        <input type="hidden" name="id_order" value="{$order_id}">
        <div class="row">
            <div class="col-md-6">
                <h5 class="mb-2">{l s='Detalii destinatar' mod='globinaryawbtracking'}</h5>
                <div class="row">
                    <div class="col-6 form-group mb-2">
                        <label class="mb-1">{l s='Ramburs:' mod='globinaryawbtracking'}</label>
                        <input type="text" name="dpd_ramburs" class="form-control form-control-sm"
                               value="{if isset($ramburs_value)}{$ramburs_value}{else}0{/if}">
                    </div>
                    <div class="col-6 form-group mb-2">
                        <label class="mb-1">{l s='Nume:' mod='globinaryawbtracking'}</label>
                        <input type="text" name="dpd_client_name" class="form-control form-control-sm"
                                {if isset($address)}
                                    {if $address->company}
                                        value="{$address->company} ({$address->firstname} {$address->lastname})"
                                    {else}
                                        value="{$address->firstname} {$address->lastname}"
                                    {/if}
                                {/if}
                               required>
                    </div>
                    <div class="col-6 form-group mb-2">
                        <label class="mb-1">{l s='Județ:' mod='globinaryawbtracking'}</label>
                        <select id="dpd_county" name="dpd_county" class="form-control form-control-sm">
                            <option value="">{l s='Selectează' mod='globinaryawbtracking'}</option>
                        </select>
                    <div id="dpd-location-alert" class="alert alert-danger mt-2 d-none"></div>
                    </div>
                    <div class="col-6 form-group mb-2">
                        <label class="mb-1">{l s='Localitate:' mod='globinaryawbtracking'}</label>
                        <select id="dpd_city" name="dpd_city" class="form-control form-control-sm" disabled>
                            <option value="">{l s='Alege județul' mod='globinaryawbtracking'}</option>
                        </select>
                    </div>
                    <div class="col-12 form-group mb-2">
                        <label class="mb-1">{l s='Adresă:' mod='globinaryawbtracking'}</label>
                        <input type="text" name="dpd_street_address" class="form-control form-control-sm"
                               value="{if isset($address)}{$address->address1}{/if}" required>
                    </div>
                    <div class="col-6 form-group mb-2">
                        <label class="mb-1">{l s='Telefon:' mod='globinaryawbtracking'}</label>
                        <input type="text" name="dpd_phone" class="form-control form-control-sm"
                               value="{if isset($address)}{$address->phone|default:$address->phone_mobile}{/if}"
                               required>
                    </div>
                    <div class="col-6 form-group mb-2">
                        <label class="mb-1">{l s='Email:' mod='globinaryawbtracking'}</label>
                        <input type="email" name="dpd_email" class="form-control form-control-sm"
                               value="{if isset($customer)}{$customer->email}{/if}" required>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <h5 class="mb-2">{l s='Produse și Servicii' mod='globinaryawbtracking'}</h5>
                <div class="row">
                    <div class="col-6 form-group mb-2">
                        <label class="mb-1">{l s='Serviciu:' mod='globinaryawbtracking'}</label>
                        <select name="dpd_service_id" class="form-control form-control-sm" required>
                            <option value="2505">{l s='STANDARD' mod='globinaryawbtracking'}</option>
                            <option value="2005">{l s='CARGO' mod='globinaryawbtracking'}</option>
                            <option value="2412">{l s='PALLET ONE' mod='globinaryawbtracking'}</option>
                        </select>
                    </div>
                    <div class="col-6 form-group mb-2">
                        <label class="mb-1">{l s='Pachete:' mod='globinaryawbtracking'}</label>
                        <input type="number" name="dpd_parcels_count" class="form-control form-control-sm" value="1"
                               min="1" required>
                    </div>
                    <div class="col-4 form-group mb-2">
                        <label class="mb-1">{l s='H (cm):' mod='globinaryawbtracking'}</label>
                        <input type="number" name="dpd_height" class="form-control form-control-sm"
                               value="{$max_height|floatval}" step="0.1" min="1" required>
                    </div>
                    <div class="col-4 form-group mb-2">
                        <label class="mb-1">{l s='Lăț (cm):' mod='globinaryawbtracking'}</label>
                        <input type="number" name="dpd_width" class="form-control form-control-sm"
                               value="{$max_width|floatval}" step="0.1" min="1" required>
                    </div>
                    <div class="col-4 form-group mb-2">
                        <label class="mb-1">{l s='Lung (cm):' mod='globinaryawbtracking'}</label>
                        <input type="number" name="dpd_length" class="form-control form-control-sm"
                               value="{$max_depth|floatval}" step="0.1" min="1" required>
                    </div>
                    <div class="col-6 form-group mb-2">
                        <label class="mb-1">{l s='Greutate (kg):' mod='globinaryawbtracking'}</label>
                        <input type="number" name="dpd_total_weight" class="form-control form-control-sm"
                               value="{$total_weight|floatval}" step="0.01" min="0.1" required>
                    </div>
                    <div class="col-6 form-group mb-2">
                        <label class="mb-1">{l s='Observații:' mod='globinaryawbtracking'}</label>
                        <textarea name="dpd_shipment_note" class="form-control form-control-sm" rows="1"></textarea>
                    </div>
                    <input type="hidden" name="extra_packages" id="extra_packages_json" value="[]">

                    <div id="extra-packages-wrapper" class="mt-3" style="display:none;">
                        <h5 class="mb-2">{l s='Pachete suplimentare' mod='globinaryawbtracking'}</h5>
                        <div id="extra-packages-container" class="row"></div>
                    </div>

                    <div class="col-6 form-group mb-2">
                        <label class="mb-1">{l s='Plătitor:' mod='globinaryawbtracking'}</label>
                        <div class="d-flex">
                            <div class="form-check mr-3">
                                <input class="form-check-input" type="radio" name="dpd_courier_payer"
                                       id="dpd_payer_sender" value="SENDER" checked>
                                <label class="form-check-label"
                                       for="dpd_payer_sender">{l s='EXP' mod='globinaryawbtracking'}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="dpd_courier_payer"
                                       id="dpd_payer_recipient" value="RECIPIENT">
                                <label class="form-check-label"
                                       for="dpd_payer_recipient">{l s='DEST' mod='globinaryawbtracking'}</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 form-group mb-2">
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="dpd_private_person"
                                   id="dpd_private_person" value="1"
                                    {if isset($address)}
                                        {if !$address->company}
                                            checked
                                        {/if}
                                    {else}
                                        checked
                                    {/if}
                            >
                            <label class="form-check-label"
                                   for="dpd_private_person">{l s='Pers. fizică' mod='globinaryawbtracking'}</label>
                        </div>
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="dpd_saturday_delivery"
                                   id="dpd_saturday_delivery" value="1">
                            <label class="form-check-label"
                                   for="dpd_saturday_delivery">{l s='Sâmbătă' mod='globinaryawbtracking'}</label>
                        </div>
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="dpd_insurance" id="dpd_insurance"
                                   value="1">
                            <label class="form-check-label"
                                   for="dpd_insurance">{l s='Asigurare' mod='globinaryawbtracking'}</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="dpd_fragile" id="dpd_fragile"
                                   value="1">
                            <label class="form-check-label"
                                   for="dpd_fragile">{l s='Fragil' mod='globinaryawbtracking'}</label>
                        </div>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" name="dpd_open_package"
                                   id="dpd_open_package" value="1">
                            <label class="form-check-label"
                                   for="dpd_open_package">{l s='Deschidere colet' mod='globinaryawbtracking'}</label>
                        </div>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" name="dsc_urgency" id="dsc_urgency"
                                   value="1">
                            <label class="form-check-label"
                                   for="dsc_urgency">{l s='DSC Tarif Urgență' mod='globinaryawbtracking'}</label>
                        </div>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" name="dsc_sms" id="dsc_sms" value="1">
                            <label class="form-check-label"
                                   for="dsc_sms">{l s='DSC SMS' mod='globinaryawbtracking'}</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="row mb-3">
        <div class="col-12 text-center">
            <button type="button" id="btn-calc-price" class="btn btn-success btn-sm" disabled>
                {l s='Calculează preț' mod='globinaryawbtracking'}
            </button>
            <div id="dpd-calc-response" class="mt-2 small"></div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12 text-center">
            <button type="button" class="btn btn-secondary btn-sm btn-issue-awb" data-issue="dpd" disabled>
                <img src="{$module_dir}data/favicon-dpd.png" alt="DPD" width="16" height="16" class="mr-1" style="vertical-align:text-bottom;"> {l s='Emite AWB DPD' mod='globinaryawbtracking'}
            </button>
            <button type="button" class="btn btn-secondary btn-sm btn-issue-awb" data-issue="sameday" disabled>
                <img src="{$module_dir}data/favicon-sameday.png" alt="Sameday" width="16" height="16" class="mr-1" style="vertical-align:text-bottom;"> {l s='Emite AWB Sameday' mod='globinaryawbtracking'}
            </button>
            <button type="button" class="btn btn-secondary btn-sm btn-issue-awb" data-issue="dsc" disabled>
                <img src="{$module_dir}data/favicon-dsc.png" alt="DSC" width="16" height="16" class="mr-1" style="vertical-align:text-bottom;"> {l s='Emite AWB DSC' mod='globinaryawbtracking'}
            </button>
            <div id="dpd-response-message" class="mt-2 small"></div>
            <div id="dpd-response-reload" class="mt-1 small text-muted"></div>
            <div class="mt-1 small text-muted">{l s='Sugestie: configurează maparea statusurilor înainte de emitere.' mod='globinaryawbtracking'}</div>
            {if $carrier_name}
                <div class="alert-info py-2 px-3 mt-2 mb-0">
                    {l s='Curier selectat de client:' mod='globinaryawbtracking'} <strong>{$carrier_name|escape:'htmlall':'UTF-8'}</strong>
                </div>
            {/if}
        </div>
    </div>

    {if isset($awb_list) && !empty($awb_list)}
        <div class="row">
            <div class="col-12">
                <h5 class="mb-2">{l s='AWB-uri' mod='globinaryawbtracking'}</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                        <tr>
                            <th>{l s='Curier' mod='globinaryawbtracking'}</th>
                            <th>{l s='Număr AWB' mod='globinaryawbtracking'}</th>
                            <th>{l s='Dată' mod='globinaryawbtracking'}</th>
                            <th>{l s='Status' mod='globinaryawbtracking'}</th>
                            <th>{l s='Acțiuni' mod='globinaryawbtracking'}</th>
                        </tr>
                        </thead>
                        <tbody>
                        {foreach from=$awb_list item=awb}
                            <tr>
                                <td>
                                    {if $awb.courier_code == 'DSC'}
                                        <img src="{$module_dir}views/img/dsc_logo.png" alt="DSC" width="70" height="35">
                                    {elseif $awb.courier_code|upper == 'SAMEDAY'}
                                        <img src="{$module_dir}views/img/sameday.png" alt="Sameday" width="70" height="35">
                                    {else}
                                        <img src="{$module_dir}views/img/dpd_logo.png" alt="DPD" width="70" height="35">
                                    {/if}
                                </td>
                                <td>{$awb.awb_number}{if $awb.parcel_number} ({$awb.parcel_number}){/if}</td>
                                <td>{$awb.awb_date_added}</td>
                                <td>
                                    {if $awb.operation_code == '-14'}
                                        <span class="badge badge-success">{$awb.current_status}</span>
                                    {elseif $awb.operation_code == '111' || $awb.operation_code == '124'}
                                        <span class="badge badge-danger">{$awb.current_status}</span>
                                    {else}
                                        <span class="badge badge-info">{$awb.current_status}</span>
                                    {/if}
                                    <div class="small text-muted">{$awb.last_status_change}</div>
                                </td>
                                <td>
                                    <a href="{$awb.tracking_url}" target="_blank"
                                       class="btn btn-info btn-sm">{l s='Track' mod='globinaryawbtracking'}</a>
                                    {if !$awb.parcel_number}
                                        <button type="button" class="btn btn-primary btn-sm btn-update-awb-status"
                                                data-awb="{$awb.awb_number}">{l s='Actualizează status' mod='globinaryawbtracking'}</button>
                                    {/if}
                                    {if !$awb.parcel_number}
                                        <button type="button" class="btn btn-danger btn-sm btn-delete-awb"
                                                data-awb="{$awb.awb_number}">{l s='Șterge' mod='globinaryawbtracking'}</button>
                                        <div class="btn-group dropright">
                                            <button type="button" class="btn btn-secondary btn-sm dropdown-toggle"
                                                    data-toggle="dropdown">
                                                {l s='Print' mod='globinaryawbtracking'}
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item btn-print-awb" data-format="A4"
                                                   data-awb="{$awb.awb_number}">{l s='A4' mod='globinaryawbtracking'}</a>
                                                <a class="dropdown-item btn-print-awb" data-format="A6"
                                                   data-awb="{$awb.awb_number}">{l s='A6' mod='globinaryawbtracking'}</a>
                                            </div>
                                        </div>
                                    {/if}
                                </td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    {/if}
</div>

{capture assign=lbl_weight}{l s='Greutate (kg)' mod='globinaryawbtracking'}{/capture}
{capture assign=lbl_h}{l s='H (cm)' mod='globinaryawbtracking'}{/capture}
{capture assign=lbl_w}{l s='Lăț (cm)' mod='globinaryawbtracking'}{/capture}
{capture assign=lbl_l}{l s='Lung (cm)' mod='globinaryawbtracking'}{/capture}
{capture assign=lbl_obs}{l s='Observații' mod='globinaryawbtracking'}{/capture}

<script type="text/javascript">
    $(document).ready(function () {
        var LABELS = {
            weight: "{$lbl_weight|escape:'javascript'}",
            h:      "{$lbl_h|escape:'javascript'}",
            w:      "{$lbl_w|escape:'javascript'}",
            l:      "{$lbl_l|escape:'javascript'}",
            obs:    "{$lbl_obs|escape:'javascript'}"
        };

        
        function canEnableAwbButtons() {
            var countySet = !!$('#dpd_county').val();
            var citySet = !!$('#dpd_city').val();
            return countySet && citySet;
        }

function setAwbButtonsEnabled(enabled) {
            $('#btn-calc-price').prop('disabled', !enabled);
            $('.btn-issue-awb').prop('disabled', !enabled);
        }

function renderExtraPackages() {
            var count = parseInt($('input[name="dpd_parcels_count"]').val() || '1', 10);
            var $wrapper = $('#extra-packages-wrapper');
            var $container = $('#extra-packages-container');

            if (count <= 1) {
                $container.empty();
                $wrapper.hide();
                $('#extra_packages_json').val('[]');
                setAwbButtonsEnabled(canEnableAwbButtons());
                return;
            }

            $container.empty();
            $wrapper.show();

            for (var i = 2; i <= count; i++) {
                var pkgIdx = i;
                var block =
                    '<div class="col-12 mb-3">' +
                    '<div class="card card-body p-3 border">' +
                    '<h6 class="mb-2">Pachet ' + pkgIdx + '</h6>' +
                    '<div class="row g-2">' +
                    '<div class="col-3">' +
                    '<label class="form-label mb-1">' + LABELS.weight + '</label>' +
                    '<input type="number" step="0.01" min="0.1" ' +
                    'class="form-control form-control-sm extra-weight" ' +
                    'data-pkg="' + pkgIdx + '">' +
                    '</div>' +
                    '<div class="col-3">' +
                    '<label class="form-label mb-1">' + LABELS.h + '</label>' +
                    '<input type="number" step="0.1" min="1" ' +
                    'class="form-control form-control-sm extra-height" ' +
                    'data-pkg="' + pkgIdx + '">' +
                    '</div>' +
                    '<div class="col-3">' +
                    '<label class="form-label mb-1">' + LABELS.w + '</label>' +
                    '<input type="number" step="0.1" min="1" ' +
                    'class="form-control form-control-sm extra-width" ' +
                    'data-pkg="' + pkgIdx + '">' +
                    '</div>' +
                    '<div class="col-3">' +
                    '<label class="form-label mb-1">' + LABELS.l + '</label>' +
                    '<input type="number" step="0.1" min="1" ' +
                    'class="form-control form-control-sm extra-length" ' +
                    'data-pkg="' + pkgIdx + '">' +
                    '</div>' +
                    '<div class="col-12 mt-2">' +
                    '<label class="form-label mb-1">' + LABELS.obs + '</label>' +
                    '<input type="text" ' +
                    'class="form-control form-control-sm extra-observations" ' +
                    'data-pkg="' + pkgIdx + '">' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';

                $container.append(block);
            }
        }

        function bakeExtraPackagesPayload() {
            var count = parseInt($('input[name="dpd_parcels_count"]').val() || '1', 10);
            var arr = [];
            if (count > 1) {
                $('#extra-packages-container .card').each(function() {
                    var $card = $(this);
                    var weight = parseFloat($card.find('.extra-weight').val() || '0');
                    var height = parseFloat($card.find('.extra-height').val() || '0');
                    var length = parseFloat($card.find('.extra-length').val() || '0');
                    var width  = parseFloat($card.find('.extra-width').val() || '0');
                    var observations = ($card.find('.extra-observations').val() || '').trim();

                    arr.push({
                        weight: isNaN(weight) ? 0 : weight,
                        height: isNaN(height) ? 0 : height,
                        length: isNaN(length) ? 0 : length,
                        width:  isNaN(width)  ? 0 : width,
                        observations: observations
                    });
                });
            }
            $('#extra_packages_json').val(JSON.stringify(arr));
        }

        $('input[name="dpd_parcels_count"]').on('input change', function() {
            renderExtraPackages();
            setAwbButtonsEnabled(canEnableAwbButtons());
        });

        $('#extra-packages-container').on('input change', 'input', function() {
            setAwbButtonsEnabled(canEnableAwbButtons());
        });

        renderExtraPackages();
        setAwbButtonsEnabled(canEnableAwbButtons());

        function getRightSiteId() {
            let dpdCity = "{$city_name}";
            let dpdCounty = "{$state_name}";

            $.ajax({
                url: '//{$smarty.server.HTTP_HOST}{$smarty.const._MODULE_DIR_}globinaryawbtracking/ajax.php',
                type: 'POST',
                data: {
                    action: 'get_right_site_id',
                    dpd_city: dpdCity,
                    dpd_county: dpdCounty,
                    token: "{Tools::encrypt(Configuration::get('PS_SHOP_NAME'))}"
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        let countyDropdown = $('#dpd_county');
                        let cityDropdown = $('#dpd_city');

                        countyDropdown.empty();
                        cityDropdown.empty();

                        $.each(response.county_list, function (index, county) {
                            countyDropdown.append('<option value="' + county + '"' +
                                (county === response.selected_county ? ' selected' : '') + '>' +
                                county + '</option>');
                        });

                        var selectedCityId = response.selected_city_id ? String(response.selected_city_id) : '';
                        $.each(response.city_list, function (index, city) {
                            var cityIdStr = String(city.site_id);
                            cityDropdown.append('<option value="' + city.site_id + '"' +
                                (cityIdStr === selectedCityId ? ' selected' : '') + '>' +
                                city.site_name_display + '</option>');
                        });

                        cityDropdown.prop('disabled', false);

                        if (!response.mapped_city) {
                            $('#dpd-location-alert').removeClass('d-none').text(response.message || 'Localitatea nu a fost mapată automat. Te rugăm selectează manual.');
                            cityDropdown.prepend('<option value="">Selectează orașul</option>');
                            cityDropdown.val('');
                            setAwbButtonsEnabled(canEnableAwbButtons());
                        } else {
                            $('#dpd-location-alert').addClass('d-none').text('');
                            setAwbButtonsEnabled(canEnableAwbButtons());
                        }
                    } else {
                        alert(response.message);
                        let countyDropdown = $('#dpd_county');
                        countyDropdown.empty().append('<option value="">Selectează județul</option>');
                        $.each(response.county_list || [], function (index, county) {
                            countyDropdown.append('<option value="' + county + '">' + county + '</option>');
                        });

                        let cityDropdown = $('#dpd_city');
                        cityDropdown.empty().append('<option value="">Selectează județul mai întâi</option>');
                        cityDropdown.prop('disabled', true);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    alert('Eroare la verificarea județului și orașului.');
                }
            });
        }

        getRightSiteId();

        $('#dpd_county').change(function () {
            setAwbButtonsEnabled(false);
            let selectedCounty = $(this).val();
            let cityDropdown = $('#dpd_city');

            if (!selectedCounty) {
                cityDropdown.prop('disabled', true).html('<option value="">Selectează județul mai întâi</option>');
                setAwbButtonsEnabled(false);
                return;
            }

            $.ajax({
                url: '//{$smarty.server.HTTP_HOST}{$smarty.const._MODULE_DIR_}globinaryawbtracking/ajax.php',
                type: 'POST',
                data: {
                    action: 'get_all_cities',
                    county: selectedCounty,
                    token: "{Tools::encrypt(Configuration::get('PS_SHOP_NAME'))}"
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        cityDropdown.prop('disabled', false).html('<option value="">Selectează orașul</option>');
                        $('#dpd-location-alert').addClass('d-none').text('');
                        $.each(response.cities, function (index, city) {
                            cityDropdown.append('<option value="' + city.site_id + '">' + city.site_name + '</option>');
                        });
                        setAwbButtonsEnabled(canEnableAwbButtons());
                    } else {
                        alert('Eroare la încărcarea orașelor.');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    alert('Eroare la încărcarea orașelor.');
                }
            });
        });

        $('#dpd_city').change(function () {
            setAwbButtonsEnabled(canEnableAwbButtons());
        });

        $('.btn-issue-awb').click(function(e) {
            e.preventDefault();
            var $btn = $(this);
            var issueType = $btn.data('issue');

            $('.btn-issue-awb').prop('disabled', true);
            $('#dpd-response-message').html("<div class='alert alert-info' role='alert'>{l s='Se procesează emiterea AWB...' mod='globinaryawbtracking'}</div>");

            bakeExtraPackagesPayload();

            $.ajax({
                url: '//{$smarty.server.HTTP_HOST}{$smarty.const._MODULE_DIR_}globinaryawbtracking/ajax.php',
                type: 'POST',
                data: $('#dpd-courier-form').serialize() +
                    '&action=issue_awb&issueButtonIs=' + issueType +
                    '&token=' + '{Tools::encrypt(Configuration::get('PS_SHOP_NAME'))}',
                dataType: 'json',
                success: function(response) {
                    if (!response.success) {
                        $('#dpd-response-message').html("<div class='alert alert-danger' role='alert'>" + response.message + "</div>");
                        $('.btn-issue-awb').prop('disabled', false);
                    } else {
                        $('#dpd-response-message').html("<div class='alert alert-success' role='alert'>" + response.message + "</div>");
                        $('#dpd-response-reload').html("<div class='alert alert-info' role='alert'>{l s='Pagina se reîncarcă...' mod='globinaryawbtracking'}</div>");
                        {if isset($smartbill_auto) && $smartbill_auto}
                        var smartbillButton = document.querySelector('.smrt-ajax[data-href*="action=exportDocument"]');
                        if (smartbillButton) {
                            var event = new MouseEvent('click', {ldelim}bubbles: true, cancelable: true, view: window{rdelim});
                            smartbillButton.dispatchEvent(event);
                            setTimeout(function() { window.location.href = window.location.href; }, 3000);
                        } else {
                            window.location.href = window.location.href;
                        }
                        {else}
                        window.location.href = window.location.href;
                        {/if}
                    }
                },
                error: function(xhr, status, error) {
                    console.log(xhr.responseText);
                    $('#dpd-response-message').html("<div class='alert alert-danger' role='alert'>{l s='A apărut o eroare. Vă rog încercați din nou.' mod='globinaryawbtracking'}</div>");
                    $('.btn-issue-awb').prop('disabled', false);
                }
            });
        });

        $('#btn-calc-price').click(function(e) {
            e.preventDefault();
            $('#btn-calc-price').prop('disabled', true);
            $('#dpd-response-message').html("<div class='alert alert-info'>{l s='Se calculează prețul...' mod='globinaryawbtracking'}</div>");

            bakeExtraPackagesPayload();

            $.ajax({
                url: '//{$smarty.server.HTTP_HOST}{$smarty.const._MODULE_DIR_}globinaryawbtracking/ajax.php',
                type: 'POST',
                data: $('#dpd-courier-form').serialize() + '&action=calculate_price&token=' + '{Tools::encrypt(Configuration::get('PS_SHOP_NAME'))}',
                dataType: 'json',
                timeout: 30000,
                success: function(response) {
                    if (!response.success) {
                        $('#dpd-response-message').html("<div class='alert alert-danger'>" + response.message + "</div>");
                    } else {
                        var resultHtml = "<div class='alert alert-success' role='alert'>" + response.message + "</div>";
                        var rankingMap = {};
                        var rankable = [];

                        function parsePrice(val) {
                            if (val === null || val === undefined || val === '') {
                                return null;
                            }
                            var normalized = String(val).replace(',', '.');
                            var n = parseFloat(normalized);
                            return isNaN(n) ? null : n;
                        }

                        if (response.dpd_price && response.dpd_price.has_data) {
                            var dpdVal = parsePrice(response.dpd_price.cost);
                            if (dpdVal !== null) {
                                rankable.push({ key: 'DPD', price: dpdVal });
                            }
                        }
                        if (response.sameday_price && response.sameday_price.has_data) {
                            var samedayVal = parsePrice(response.sameday_price.cost);
                            if (samedayVal !== null) {
                                rankable.push({ key: 'Sameday', price: samedayVal });
                            }
                        }
                        if (response.dsc_price && response.dsc_price.has_data) {
                            var dscVal = parsePrice(response.dsc_price.cost);
                            if (dscVal !== null) {
                                rankable.push({ key: 'DSC', price: dscVal });
                            }
                        }

                        if (rankable.length >= 2) {
                            rankable.sort(function(a, b) { return a.price - b.price; });
                            rankingMap[rankable[0].key] = { tag: 'CEL MAI IEFTIN', css: 'success' };
                            rankingMap[rankable[rankable.length - 1].key] = { tag: 'CEL MAI SCUMP', css: 'danger' };
                            if (rankable.length === 3) {
                                rankingMap[rankable[1].key] = { tag: 'MEDIU', css: 'warning' };
                            }
                        }

                        function buildCourierLine(label, dataObj) {
                            if (!dataObj) {
                                return '';
                            }
                            var rank = rankingMap[label] || null;
                            var cssClass = rank ? ('list-group-item-' + rank.css) : '';
                            var badge = rank ? (" <span class='badge badge-" + rank.css + " ml-2'>" + rank.tag + "</span>") : '';

                            if (dataObj.has_data) {
                                return "<li class='list-group-item " + cssClass + "'>" + label + ": <strong>" + dataObj.cost + " RON</strong> – " + dataObj.message + badge + "</li>";
                            }
                            return "<li class='list-group-item'>" + label + ": " + dataObj.message + "</li>";
                        }

                        resultHtml += "<ul class='list-group'>";
                        resultHtml += buildCourierLine('DPD', response.dpd_price);
                        resultHtml += buildCourierLine('Sameday', response.sameday_price);
                        resultHtml += buildCourierLine('DSC', response.dsc_price);
                        resultHtml += "</ul>";

                        if (rankable.length >= 2) {
                            resultHtml += "<div class='mt-2 small text-muted'>";
                            resultHtml += "<span class='badge badge-success mr-1'>CEL MAI IEFTIN</span>";
                            resultHtml += "<span class='badge badge-warning mr-1'>MEDIU</span>";
                            resultHtml += "<span class='badge badge-danger'>CEL MAI SCUMP</span>";
                            resultHtml += "</div>";
                        }

                        $('#dpd-response-message').html(resultHtml);
                    }
                    $('#btn-calc-price').prop('disabled', false);
                },
                error: function(xhr, status, error) {
                    console.log(xhr.responseText);
                    if (status === 'timeout') {
                        $('#dpd-response-message').html("<div class='alert alert-danger'>{l s='Cererea a expirat. Verifică setările curierilor și încearcă din nou.' mod='globinaryawbtracking'}</div>");
                    } else {
                        $('#dpd-response-message').html("<div class='alert alert-danger'>{l s='A apărut o eroare. Vă rog încercați din nou.' mod='globinaryawbtracking'}</div>");
                    }
                    $('#btn-calc-price').prop('disabled', false);
                }
            });
        });

        $('.btn-delete-awb').click(function (e) {
            e.preventDefault();

            let awbNumber = $(this).data('awb');

            if (!confirm("{l s='Ești sigur că vrei să ștergi acest AWB=' mod='globinaryawbtracking'}" + awbNumber + "?")) {
                return;
            }

            $('#dpd-response-message').html("<div class='alert alert-info'>{l s='Se șterge AWB...' mod='globinaryawbtracking'}</div>");

            $.ajax({
                url: '//{$smarty.server.HTTP_HOST}{$smarty.const._MODULE_DIR_}globinaryawbtracking/ajax.php',
                type: 'POST',
                data: {
                    action: 'delete_awb',
                    id_order: '{$order_id}',
                    awb_number: awbNumber,
                    token: "{Tools::encrypt(Configuration::get('PS_SHOP_NAME'))}"
                },
                dataType: 'json',
                success: function (response) {
                    if (!response.success) {
                        $('#dpd-response-message').html("<div class='alert alert-danger'>" + response.message + "</div>");
                    } else {
                        $('#dpd-response-message').html("<div class='alert alert-success'>" + response.message + "</div>");
                        $('#dpd-response-reload').html("<div class='alert alert-info'>{l s='Pagina se reîncarcă...' mod='globinaryawbtracking'}</div>");
                        location.reload();
                    }
                },
                error: function (xhr, status, error) {
                    console.log(xhr.responseText);
                    $('#dpd-response-message').html("<div class='alert alert-danger'>{l s='A apărut o eroare. Vă rog încercați din nou.' mod='globinaryawbtracking'}</div>");
                }
            });
        });

        // DPD UPDATE STATUS BUTTON
        $('.btn-update-awb-status').click(function (e) {
            e.preventDefault();
            let awbNumber = $(this).data('awb');
            if (!awbNumber) {
                return;
            }

            $('#dpd-response-message').html("<div class='alert alert-info'>{l s='Se actualizează statusul...' mod='globinaryawbtracking'}</div>");

            $.ajax({
                url: '//{$smarty.server.HTTP_HOST}{$smarty.const._MODULE_DIR_}globinaryawbtracking/ajax.php',
                type: 'POST',
                data: {
                    action: 'update_awb_status',
                    awb_number: awbNumber,
                    token: "{Tools::encrypt(Configuration::get('PS_SHOP_NAME'))}"
                },
                dataType: 'json',
                success: function (response) {
                    if (!response.success) {
                        $('#dpd-response-message').html("<div class='alert alert-danger'>" + response.message + "</div>");
                    } else {
                        $('#dpd-response-message').html("<div class='alert alert-success'>" + response.message + "</div>");
                        $('#dpd-response-reload').html("<div class='alert alert-info'>{l s='Pagina se reîncarcă...' mod='globinaryawbtracking'}</div>");
                        location.reload();
                    }
                },
                error: function (xhr, status, error) {
                    console.log(xhr.responseText);
                    $('#dpd-response-message').html("<div class='alert alert-danger'>{l s='A apărut o eroare. Vă rog încercați din nou.' mod='globinaryawbtracking'}</div>");
                }
            });
        });

        $('.btn-print-awb').click(function (e) {
            e.preventDefault();

            let awbNumber = $(this).data('awb');
            if (!awbNumber) {
                alert('Nu există un AWB de tipărit.');
                return;
            }

            let printFormat = $(this).data('format');

            $('#dpd-response-message').html("<div class='alert alert-info'>Se tipărește AWB...</div>");

            $.ajax({
                url: '//{$smarty.server.HTTP_HOST}{$smarty.const._MODULE_DIR_}globinaryawbtracking/ajax.php',
                type: 'POST',
                data: {
                    action: 'print_awb',
                    awb_number: awbNumber,
                    format: printFormat,
                    token: "{Tools::encrypt(Configuration::get('PS_SHOP_NAME'))}"
                },
                xhrFields: {
                    responseType: 'blob'
                },
                success: function (data, status, xhr) {
                    let contentType = (xhr.getResponseHeader('Content-Type') || '').toLowerCase();
                    let filename = "awb_" + awbNumber + ".pdf";
                    {literal}
                    let blobData = null;
                    if (data instanceof Blob) {
                        blobData = data;
                    } else if (data && typeof data === 'object') {
                        try {
                            blobData = new Blob([data]);
                        } catch (e) {
                            blobData = null;
                        }
                    } else if (typeof data === 'string') {
                        blobData = new Blob([data]);
                    }
                    {/literal}

                    if (contentType.indexOf('application/pdf') === -1) {
                        if (!blobData) {
                            $('#dpd-response-message').html("<div class='alert alert-danger'>Răspuns invalid la tipărire. Te rugăm încearcă din nou.</div>");
                            return;
                        }
                        let reader = new FileReader();
                        reader.onload = function () {
                            let msg = 'Răspuns invalid la tipărire. Te rugăm încearcă din nou.';
                            let raw = '';
                            try {
                                raw = (reader.result || '').toString();
                                let parsed = JSON.parse(raw || '{}');
                                if (parsed && parsed.message) {
                                    msg = parsed.message;
                                }
                            } catch (e) {
                                if (raw && raw.trim()) {
                                    msg = raw.trim().substring(0, 300);
                                }
                            }
                            $('#dpd-response-message').html("<div class='alert alert-danger'>" + msg + "</div>");
                        };
                        reader.readAsText(blobData);
                        return;
                    }

                    if (!blobData || !blobData.size || blobData.size < 100) {
                        $('#dpd-response-message').html("<div class='alert alert-danger'>Fișierul AWB primit este gol. Te rugăm încearcă din nou.</div>");
                        return;
                    }

                    let blob = new Blob([blobData]);
                    let link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    $('#dpd-response-message').html("<div class='alert alert-success'>AWB descărcat cu succes.</div>");
                },
                error: function (xhr, status, error) {
                    console.log(xhr.responseText);
                    $('#dpd-response-message').html("<div class='alert alert-danger'>A apărut o eroare. Vă rog încercați din nou.</div>");
                }
            });
        });
    });
</script>
