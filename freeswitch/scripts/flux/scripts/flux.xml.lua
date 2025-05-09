-------------------------------------------------------------------------------------
-- Flux SBC - Unindo pessoas e negócios
--
-- Copyright (C) 2022 Flux Telecom
-- Daniel Paixao <daniel@flux.net.br>
-- Flux SBC Version 4.0 and above
-- License https://www.gnu.org/licenses/agpl-3.0.html
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU Affero General Public License as
-- published by the Free Software Foundation, either version 3 of the
-- License, or (at your option) any later version.
-- 
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU Affero General Public License for more details.
-- 
-- You should have received a copy of the GNU Affero General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------------------

-- Dialplan header part
function freeswitch_xml_header(xml,destination_number,accountcode,maxlength,call_direction,accountname,xml_user_rates,customer_userinfo,config,xml_did_rates,reseller_cc_limit,callerid_array,original_destination_number)
	local callstart = os.date("!%Y-%m-%d %H:%M:%S")

	table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
	table.insert(xml, [[<document type="freeswitch/xml">]]);
	table.insert(xml, [[<section name="dialplan" description="FLUX Dialplan">]]);
	table.insert(xml, [[<context name="]]..params:getHeader("Caller-Context")..[[">]]);
	table.insert(xml, [[<extension name="]]..destination_number..[[">]]); 
	table.insert(xml, [[<condition field="destination_number" expression="]]..plus_destination_number(params:getHeader("Caller-Destination-Number"))..[[">]]);
	table.insert(xml, [[<action application="set" data="effective_destination_number=]]..plus_destination_number(original_destination_number)..[["/>]]);
	Logger.debug("maxlength::::::::: "..maxlength);
	table.insert(xml, [[<action application="set" data="bridge_pre_execute_bleg_app=sched_hangup"/>]]);
	table.insert(xml, [[<action application="set" data="bridge_pre_execute_bleg_data=+]]..((maxlength) * 60)..[[ normal_clearing"/>]]);
     
	table.insert(xml, [[<action application="set" data="callstart=]]..callstart..[["/>]]);
	table.insert(xml, [[<action application="set" data="hangup_after_bridge=true"/>]]);

	-- Made it configurable if someone want to set continue_on_fail for specific disposition	
	local continue_on_fail = '!USER_BUSY'
	if (config['continue_on_fail'] ~= nil) then
		continue_on_fail = config['continue_on_fail']
		table.insert(xml, [[<action application="set" data="continue_on_fail=]]..continue_on_fail..[["/>]]);
	 else
	 table.insert(xml, [[<action application="set" data="continue_on_fail=true"/>]]); 
	end

	     
	table.insert(xml, [[<action application="set" data="ignore_early_media=false"/>]]);       

	table.insert(xml, [[<action application="set" data="account_id=]]..customer_userinfo['id']..[["/>]]);              
	table.insert(xml, [[<action application="set" data="parent_id=]]..customer_userinfo['reseller_id']..[["/>]]);
	table.insert(xml, [[<action application="set" data="entity_id=]]..customer_userinfo['type']..[["/>]]);
	table.insert(xml, [[<action application="set" data="call_processed=internal"/>]]);    
	table.insert(xml, [[<action application="export" data="user_domain=$${domain_name}"/>]]);
	table.insert(xml, [[<action application="set" data="call_direction=]]..call_direction..[["/>]]);
	table.insert(xml, [[<action application="set" data="accountcode=]]..accountcode..[["/>]]);
	table.insert(xml, [[<action application="set" data="accountname=]]..accountname..[["/>]]);
	if (package_id and tonumber(package_id) > 0) then
		table.insert(xml, [[<action application="set" data="package_id=]]..package_id..[["/>]]);              
	end
	if (call_direction == "inbound" and tonumber(config['inbound_fax']) > 0) then
		table.insert(xml, [[<action application="export" data="t38_passthru=true"/>]]);    
		table.insert(xml, [[<action application="set" data="fax_enable_t38=true"/>]]);    
		table.insert(xml, [[<action application="set" data="fax_enable_t38_request=true"/>]]);    
	elseif (call_direction == "outbound" and tonumber(config['outbound_fax']) > 0) then
		table.insert(xml, [[<action application="export" data="t38_passthru=true"/>]]);    
		table.insert(xml, [[<action application="set" data="fax_enable_t38=true"/>]]);    
		table.insert(xml, [[<action application="set" data="fax_enable_t38_request=true"/>]]);    
	end
	--custom outbound        
	if custom_outbound then custom_outbound(xml) end 

	if(tonumber(config['balance_announce']) == 0) then
		table.insert(xml, [[<action application="sleep" data="1000"/>]]);
		table.insert(xml, [[<action application="playback" data="/usr/share/freeswitch/sounds/pt/BR/karina/ivr/8000/ivr-account_balance_is.wav"/>]]);
		local tmp_prefix=''
		if get_international_balance_prefix then tmp_prefix = get_international_balance_prefix(customer_userinfo) end 	

		customer_balance = tonumber(customer_userinfo['posttoexternal']) == 1 and tonumber(customer_userinfo[tmp_prefix..'credit_limit'])+(tonumber(customer_userinfo[tmp_prefix..'balance'])*(-1)) or tonumber(customer_userinfo[tmp_prefix..'balance'])

		table.insert(xml, [[<action application="say" data="en CURRENCY PRONOUNCED ]].. customer_balance..[["/>]]);

	end
	if(tonumber(config['minutes_announce']) == 0) then
		table.insert(xml, [[<action application="sleep" data="500"/>]]);
		table.insert(xml, [[<action application="playback" data="/usr/share/freeswitch/sounds/pt/BR/karina/flux-this-call-will-last.wav"/>]]);
		table.insert(xml, [[<action application="say" data="en NUMBER PRONOUNCED ]].. math.floor(maxlength)..[["/>]]);
		table.insert(xml, [[<action application="playback" data="/usr/share/freeswitch/sounds/pt/BR/karina/time/8000/minute.wav"/>]]);       
	end
	if (call_direction == "inbound") then 
		table.insert(xml, [[<action application="set" data="origination_rates_did=]]..xml_did_rates..[["/>]]);
	else
		table.insert(xml, [[<action application="set" data="origination_rates=]]..xml_user_rates..[["/>]]);
	end

	if(xml_did_rates ~= nil and xml_did_rates ~= '') then
		table.insert(xml, [[<action application="set" data="origination_rates=]]..xml_did_rates..[["/>]]);
	end
	
	-- Set original caller id for CDRS
    if (callerid_array['original_cid_name'] ~= '' and callerid_array['original_cid_name'] ~= '<null>')  then
            table.insert(xml, [[<action application="set" data="original_caller_id_name=]]..callerid_array['original_cid_name']..[["/>]]);
    end
    if (callerid_array['cid_number'] ~= '' and callerid_array['cid_number'] ~= '<null>')  then
            table.insert(xml, [[<action application="set" data="original_caller_id_number=]]..callerid_array['original_cid_number']..[["/>]]);
    end
       
	-- Set max channel limit for user if > 0
	if(tonumber(customer_userinfo['maxchannels']) > 0) then    		
	    	table.insert(xml, [[<action application="limit" data="db ]]..accountcode..[[ user_]]..accountcode..[[ ]]..customer_userinfo['maxchannels']..[[ !SWITCH_CONGESTION"/>]]);
	end

	-- Set CPS limit for user if > 0
	if (tonumber(customer_userinfo['cps']) > 0) then
		table.insert(xml, [[<action application="limit" data="hash CPS_]]..accountcode..[[ CPS_user_]]..accountcode..[[ ]]..customer_userinfo['cps']..[[/1 !SWITCH_CONGESTION"/>]]);
	end

    -- Set max channel limit for resellers
    if (reseller_cc_limit ~= nil) then
        table.insert(xml, reseller_cc_limit);
    end   
    
	if(tonumber(customer_userinfo['is_recording']) == 1) then 
		table.insert(xml, [[<action application="export" data="is_recording=1"/>]]);
		table.insert(xml, [[<action application="export" data="media_bug_answer_req=true"/>]]);
		table.insert(xml, [[<action application="export" data="RECORD_STEREO=true"/>]]);
		table.insert(xml, [[<action application="export" data="record_sample_rate=8000"/>]]);
		table.insert(xml, [[<action application="export" data="execute_on_answer=record_session $${recordings_dir}/${uuid}.wav"/>]]);
	end
	return xml
end

function urlencode(str)
	if (str) then
	str = string.gsub (str, "\n", "\r\n")
	str = string.gsub (str, "([^%w ])",
	function (c) return string.format ("%%%02X", string.byte(c)) end)
		str = string.gsub (str, " ", "+")
	end
	return str    
end

-- Dialplan footer part
function freeswitch_xml_footer(xml)
	table.insert(xml,[[</condition>]]);
	table.insert(xml,[[</extension>]]);
	table.insert(xml,[[</context>]]);
	table.insert(xml,[[</section>]]);
	table.insert(xml,[[</document>]]);
	return xml
end

-- Dialplan for outbound calls
function freeswitch_xml_outbound(xml,destination_number,outbound_info,callerid_array,rate_group_id,old_trunk_id,force_outbound_routes,rategroup_type,livecall_data)

	local tr_localization_tunk=nil
	tr_localization_tunk = get_localization(outbound_info['trunk_id'],'Trunk')
	if (tr_localization_tunk ~= nil) then
		tr_localization_tunk['out_caller_id_terminate'] = tr_localization_tunk['out_caller_id_terminate']:gsub(" ", "")
		callerid_array['cid_name'] = do_number_translation(tr_localization_tunk['out_caller_id_terminate'],callerid_array['original_cid_name'])
		callerid_array['cid_number'] = do_number_translation(tr_localization_tunk['out_caller_id_terminate'],callerid_array['original_cid_number'])
		tr_localization_tunk['number_terminate'] = tr_localization_tunk['number_terminate']:gsub(" ", "")
		destination_number = do_number_translation(tr_localization_tunk['number_terminate'],destination_number)
	end
	local temp_destination_number = destination_number
	local tr_localization=nil
	tr_localization = get_localization(outbound_info['provider_id'],'T')
	
	if (tr_localization ~= nil) then
		tr_localization['out_caller_id_terminate'] = tr_localization['out_caller_id_terminate']:gsub(" ", "")
		-------------- Caller Id translation ---------	 
		callerid_array['cid_name'] = do_number_translation(tr_localization['out_caller_id_terminate'],callerid_array['cid_name'])
		callerid_array['cid_number'] = do_number_translation(tr_localization['out_caller_id_terminate'],callerid_array['cid_number'])    
		xml = freeswitch_xml_callerid(xml,callerid_array)	    	   	    
    	----------------------------------------------------------------------

    	-------------- Destination number translation ---------
		tr_localization['number_terminate'] = tr_localization['number_terminate']:gsub(" ", "")
		temp_destination_number = do_number_translation(tr_localization['number_terminate'],destination_number)
		-----------------------------------
		
	end
	xml = freeswitch_xml_callerid(xml,callerid_array)
	if(outbound_info['prepend'] ~= '' or outbound_info['strip'] ~= '') then

        if (outbound_info['prepend'] == '') then 
            outbound_info['prepend'] = '*'                        
        end

        if (outbound_info['strip'] == '') then 
            outbound_info['strip'] = '*'
        end

		temp_destination_number = do_number_translation(outbound_info['strip'].."/"..outbound_info['prepend'],temp_destination_number)
	end
    if (outbound_info ~= nil and tonumber(outbound_info['rn1']) ~=nil and tonumber(carrier_info['rn1']) > 0) then
		idCadup = outbound_info['idCadup']
		carrier_id = carrier_info['carrier_id']
		nomeLocalidade = outbound_info['nomeLocalidade']
		nomePrestadora = outbound_info['nomePrestadora']
		areaLocal = outbound_info['areaLocal']
		tipo = outbound_info['tipo']
		prefixo = outbound_info['prefixo']
		codArea = outbound_info['codArea']
		uf = outbound_info['uf']
		rn1 = outbound_info['rn1']
		
		carrier_rn1 = outbound_info['carrier_rn1']
		call_count = outbound_info['call_count']
		carrier_name = outbound_info['carrier_name']
		carrier_route_id = carrier_info['carrier_route_id']
						
	
		Logger.debug("idCadup : "..idCadup)
		Logger.debug("carrier_id : "..carrier_id)
		Logger.debug("nomeLocalidade : "..nomeLocalidade)
		Logger.debug("nomePrestadora : "..nomePrestadora)
		Logger.debug("areaLocal : "..areaLocal)
		Logger.debug("tipo : "..tipo)
		Logger.debug("prefixo : "..prefixo)
		Logger.debug("carrier_rn1 : "..carrier_rn1)
		Logger.debug("carrier_name : "..carrier_name)
		Logger.debug("carrier_route_id : "..carrier_route_id)
		Logger.debug("call_count : "..call_count)
		Logger.debug("codArea : "..codArea)
		Logger.debug("uf : "..uf)
		Logger.debug("rn1 : "..rn1)
		table.insert(xml, [[<action application="export" data="idCadup=]]..idCadup..[["/>]]);
		table.insert(xml, [[<action application="set" data="check_cadup=true"/>]]);
		table.insert(xml, [[<action application="export" data="routing_type=4"/>]]);
		table.insert(xml, [[<action application="export" data="rate_flag=4"/>]]);
		table.insert(xml, [[<action application="export" data="carrier_id=]]..carrier_id..[["/>]]);
		table.insert(xml, [[<action application="export" data="nomeLocalidade=]]..nomeLocalidade..[["/>]]);
		table.insert(xml, [[<action application="export" data="nomePrestadora=]]..nomePrestadora..[["/>]]);
		table.insert(xml, [[<action application="export" data="areaLocal=]]..areaLocal..[["/>]]);
		table.insert(xml, [[<action application="export" data="tipo=]]..tipo..[["/>]])    
		table.insert(xml, [[<action application="export" data="prefixo=]]..prefixo..[["/>]]);
		table.insert(xml, [[<action application="export" data="codArea=]]..codArea..[["/>]]);
		table.insert(xml, [[<action application="export" data="uf=]]..uf..[["/>]]);
		table.insert(xml, [[<action application="export" data="rn1=]]..carrier_rn1..[["/>]]);
		
		table.insert(xml, [[<action application="export" data="carrier_route_id=]]..carrier_route_id..[["/>]]);
		table.insert(xml, [[<action application="export" data="carrier_rn1=]]..carrier_rn1..[["/>]]);
		table.insert(xml, [[<action application="export" data="carrier_name=]]..carrier_name..[["/>]]);
		
		table.insert(xml, [[<action application="export" data="provider_id=]]..outbound_info['provider_id']..[["/>]]);
		table.insert(xml, [[<action application="export" data="call_type_custom=]]..tipo..[["/>]]);
    else 
        outbound_info['idCadup'] = 0;
        outbound_info['carrier_route_id'] = 0;
        outbound_info['carrier_id'] = 0;
        carrier_rn1 = 0
        carrier_id = 0
        carrier_route_id = 0
        table.insert(xml, [[<action application="set" data="rate_flag=]]..outbound_info['outbound_route_id']..[["/>]]);
        table.insert(xml, [[<action application="export" data="idCadup=0"/>]]);
        table.insert(xml, [[<action application="set" data="check_cadup=false"/>]]);
        table.insert(xml, [[<action application="export" data="routing_type=1"/>]]);
        
--        table.insert(xml, [[<action application="export" data="rate_flag=1"/>]]);
    end
	xml_termination_rates= "ID:"..outbound_info['outbound_route_id'].."|CODE:"..outbound_info['pattern'].."|DESTINATION:"..outbound_info['comment'].."|CONNECTIONCOST:"..outbound_info['connectcost'].."|INCLUDEDSECONDS:"..outbound_info['includedseconds'].."|IDCADUP:"..outbound_info['idCadup'].."|COST:"..outbound_info['cost'].."|CARRIER_ROUTE_ID:"..carrier_route_id.."|CARRIER_ID:"..carrier_id.."|INC:"..outbound_info['inc'].."|INITIALBLOCK:"..outbound_info['init_inc'].."|TRUNK:"..outbound_info['trunk_id'].."|PROVIDER:"..outbound_info['provider_id'];
	if(params:getHeader("variable_sip_h_P-Voice_broadcast") == 'true')then
		local sip_user = params:getHeader("variable_sip_h_P-Voice_broadcast_type")
		table.insert(xml, [[<action application="set" data="calltype=BROADCAST"/>]]);
		table.insert(xml, [[<action application="set" data="sip_user=]]..sip_user..[["/>]]);
	else
		table.insert(xml, [[<action application="set" data="calltype=]]..rategroup_type..[["/>]]);        
	end
	table.insert(xml, [[<action application="set" data="termination_rates=]]..xml_termination_rates..[["/>]]);    
	table.insert(xml, [[<action application="set" data="trunk_id=]]..outbound_info['trunk_id']..[["/>]]);        
	table.insert(xml, [[<action application="set" data="provider_id=]]..outbound_info['provider_id']..[["/>]]);           
--	table.insert(xml, [[<action application="set" data="rate_flag=]]..rategroup_type..[["/>]]);           
	table.insert(xml, [[<action application="set" data="force_trunk_flag=]]..force_outbound_routes..[["/>]]);    
    table.insert(xml, [[<action application="export" data="presence_data=trunk_id=]]..outbound_info['trunk_id']..[["/>]])
    table.insert(xml, [[<action application="set" data="intcall=]]..(outbound_info['intcall'] and 1 or 0)..[["/>]])      
--	table.insert(xml, [[<action application="unset" data="direction"/>]]);
--	table.insert(xml, [[<action application="set" data="direction=outbound"/>]]);
--	table.insert(xml, [[<action application="export" data="direction=outbound"/>]]);	

	-- Check if is there any gateway configuration params available for it.
	if (outbound_info['dialplan_variable'] ~= '') then 
		Logger.debug(" ".. outbound_info['dialplan_variable']);
		local dialplan_variable = split(outbound_info['dialplan_variable'],",")      
		for dialplan_variable_key,dialplan_variable_value in pairs(dialplan_variable) do
			local dialplan_variable_data = split(dialplan_variable_value,"=")  
			Logger.debug("[GATEWAY VARIABLE ] : "..dialplan_variable_data[1] );
			if( dialplan_variable_data[1] ~= nil and dialplan_variable_data[2] ~= nil) then
				table.insert(xml, [[<action application="set" data="]]..dialplan_variable_data[1].."="..dialplan_variable_data[2]..[["/>]]);           	    
			end
		end             
	end
	----------------------- END Gateway configuraiton -------------------------------
	-- Set force codec if configured
	
	--~ livecall_data = livecall_data.."|||"..outbound_info['trunk_name'].." // "..outbound_info['pattern'].." // "..outbound_info['comment'].." // "..outbound_info['cost']
	
	if(outbound_info['trunk_id']~=nil) then
		livecall_data = livecall_data.."|||"..outbound_info['trunk_name'].." // "..outbound_info['pattern'].." // "..outbound_info['comment'].." // "..outbound_info['cost'].." // trunk_id="..outbound_info['trunk_id']
	else
		livecall_data = livecall_data.."|||"..outbound_info['trunk_name'].." // "..outbound_info['pattern'].." // "..outbound_info['comment'].." // "..outbound_info['cost']
	end
	
    table.insert(xml, [[<action application="export" data="presence_data=]]..livecall_data..[[|||STD"/>]])
	
    chan_var = "leg_timeout="..outbound_info['leg_timeout']
    if (tonumber(outbound_info['rn1']) ~=nil and tonumber(outbound_info['rn1']) > 0) then
    chan_var = chan_var..",carrier_id="..carrier_id..",nomeLocalidade='"..outbound_info['nomeLocalidade'].."',nomePrestadora='"..outbound_info['nomePrestadora'].."',areaLocal="..outbound_info['areaLocal']..",tipo="..outbound_info['tipo']..",prefixo="..outbound_info['prefixo']..",codArea="..outbound_info['codArea']..",uf="..outbound_info['uf']..",rn1="..outbound_info['rn1']
    end
    p_id_var = "{sip_cid_type="..outbound_info['sip_cid_type'].."}"
    if (outbound_info['codec'] ~= '') then
            chan_var = chan_var..",absolute_codec_string=".."^^:"..outbound_info['codec']:gsub("%,", ":")
     else
	if(params:getHeader('variable_sip_from_user') and params:getHeader('variable_sip_from_user') ~= "")then
		local sip_codec = get_sip_codec(params:getHeader('variable_sip_from_user'))
		if(sip_codec and sip_codec ~= "")then
			Logger.debug("[XML] sip_codec : "..sip_codec)
			chan_var = chan_var..",absolute_codec_string=".."^^:"..sip_codec:gsub("%,", ":")
		end
	end
            
    end            
--     table.insert(xml, [[<action application="info" data=""/>]]);
	-- Set CPS limit for user if > 0
	if (tonumber(outbound_info['cps']) ~=nil and tonumber(outbound_info['cps']) > 0) then
		table.insert(xml, [[<action application="limit" data="hash CPS_]]..outbound_info['trunk_id']..[[ CPS_trunk_]]..outbound_info['trunk_id']..[[ ]]..outbound_info['cps']..[[/1 !SWITCH_CONGESTION"/>]]);
	end

	if(tonumber(outbound_info['maxchannels']) > 0) then    
		table.insert(xml, [[<action application="limit_execute" data="db ]]..outbound_info['path']..[[ gw_]]..outbound_info['path']..[[ ]]..outbound_info['maxchannels']..[[ bridge ]]..p_id_var..[[[]]..chan_var..[[]sofia/gateway/]]..outbound_info['path']..[[/]]..temp_destination_number..[["/>]]);
	else
		table.insert(xml, [[<action application="bridge" data="]]..p_id_var..[[[]]..chan_var..[[]sofia/gateway/]]..outbound_info['path']..[[/]]..temp_destination_number..[["/>]]);
	end
	if(outbound_info['path1'] ~= '' and outbound_info['path1'] ~= outbound_info['path']) then
--		table.insert(xml, [[<action application="info" data=""/>]]);
		table.insert(xml, [[<action application="bridge" data="]]..p_id_var..[[[]]..chan_var..[[]sofia/gateway/]]..outbound_info['path1']..[[/]]..temp_destination_number..[["/>]]);
	end

	if(outbound_info['path2'] ~= '' and outbound_info['path2'] ~= outbound_info['path'] and outbound_info['path2'] ~= outbound_info['path1']) then
		table.insert(xml, [[<action application="bridge" data="]]..p_id_var..[[[]]..chan_var..[[]sofia/gateway/]]..outbound_info['path2']..[[/]]..temp_destination_number..[["/>]]);
	end
    return xml
end

-- Dialplan for inbound calls
function freeswitch_xml_inbound(xml,didinfo,userinfo,config,xml_did_rates,callerid_array,livecall_data)
	local is_local_extension = "0"
	callerid_array['cid_name'] = do_number_translation(didinfo['did_cid_translation'],callerid_array['cid_name'])
	callerid_array['cid_number'] = do_number_translation(didinfo['did_cid_translation'],callerid_array['cid_number'])
	--if(tonumber(didinfo['maxchannels']) > 0) then    		
	--    	table.insert(xml, [[<action application="limit" data="db ]]..didinfo['accountid']..[[ user_]]..didinfo['accountid']..[[ ]]..didinfo['maxchannels']..[[ !SWITCH_CONGESTION"/>]]);
	--end
	if (tonumber(userinfo['localization_id']) > 0 and or_localization and or_localization['in_caller_id_originate'] ~= nil) then     
		callerid_array['cid_name'] = do_number_translation(or_localization['in_caller_id_originate'],callerid_array['cid_name'])
		callerid_array['cid_number'] = do_number_translation(or_localization['in_caller_id_originate'],callerid_array['cid_number'])
	end   
	xml = freeswitch_xml_callerid(xml,callerid_array)	    	       
	table.insert(xml, [[<action application="set" data="receiver_accid=]]..didinfo['accountid']..[["/>]]);  
	if(tonumber(didinfo['maxchannels']) > 0) then    
	    table.insert(xml, [[<action application="limit" data="db ]]..destination_number..[[ did_]]..destination_number..[[ ]]..didinfo['maxchannels']..[[ !SWITCH_CONGESTION"/>]]);        
	end
	if callerid_lookup_dialplan then callerid_lookup_dialplan(xml,didinfo) end
	if (didinfo ~= nil and tonumber(didinfo['rn1']) ~=nil and tonumber(didinfo['rn1']) > 0) then
	idCadup = didinfo['idCadup']
	carrier_id = didinfo['carrier_id']
	nomeLocalidade = didinfo['nomeLocalidade']
	nomePrestadora = didinfo['nomePrestadora']
	areaLocal = didinfo['areaLocal']
	tipo = didinfo['tipo']
	prefixo = didinfo['prefixo']
	codArea = didinfo['codArea']
	uf = didinfo['uf']
	rn1 = didinfo['rn1']
	
	carrier_rn1 = didinfo['carrier_rn1']
	call_count = didinfo['call_count']
	carrier_name = didinfo['carrier_name']
	carrier_route_id = didinfo['carrier_route_id']
					

	Logger.debug("idCadup : "..idCadup)
	Logger.debug("carrier_id : "..carrier_id)
	Logger.debug("nomeLocalidade : "..nomeLocalidade)
	Logger.debug("nomePrestadora : "..nomePrestadora)
	Logger.debug("areaLocal : "..areaLocal)
	Logger.debug("tipo : "..tipo)
	Logger.debug("prefixo : "..prefixo)
	Logger.debug("carrier_rn1 : "..carrier_rn1)
	Logger.debug("carrier_name : "..carrier_name)
	Logger.debug("carrier_route_id : "..carrier_route_id)
	Logger.debug("call_count : "..call_count)
	Logger.debug("codArea : "..codArea)
	Logger.debug("uf : "..uf)
	Logger.debug("rn1 : "..rn1)
	table.insert(xml, [[<action application="export" data="idCadup=]]..idCadup..[["/>]]);
	table.insert(xml, [[<action application="export" data="carrier_id=]]..carrier_id..[["/>]]);
	table.insert(xml, [[<action application="export" data="nomeLocalidade=]]..nomeLocalidade..[["/>]]);
	table.insert(xml, [[<action application="export" data="nomePrestadora=]]..nomePrestadora..[["/>]]);
	table.insert(xml, [[<action application="export" data="areaLocal=]]..areaLocal..[["/>]]);
	table.insert(xml, [[<action application="export" data="tipo=]]..tipo..[["/>]])    
	table.insert(xml, [[<action application="export" data="prefixo=]]..prefixo..[["/>]]);
	table.insert(xml, [[<action application="export" data="codArea=]]..codArea..[["/>]]);
	table.insert(xml, [[<action application="export" data="uf=]]..uf..[["/>]]);
	table.insert(xml, [[<action application="export" data="rn1=]]..carrier_rn1..[["/>]]);
	table.insert(xml, [[<action application="export" data="carrier_route_id=]]..carrier_route_id..[["/>]]);
	table.insert(xml, [[<action application="export" data="carrier_rn1=]]..carrier_rn1..[["/>]]);
	table.insert(xml, [[<action application="export" data="did_reverse_rate=]]..didinfo['reverse_rate']..[["/>]]);
	table.insert(xml, [[<action application="export" data="did_rate=]]..didinfo['rate_group']..[["/>]]);
--	table.insert(xml, [[<action application="export" data="provider_id=]]..didinfo['provider_id']..[["/>]]);
--	table.insert(xml, [[<action application="export" data="call_type_custom=]]..tipo..[["/>]]);
	end
	
	table.insert(xml, [[<action application="export" data="presence_data=]]..livecall_data..[[||||||DID"/>]])
	table.insert(xml, [[<action application="export" data="call_type=]]..didinfo['call_type']..[["/>]])
	local custom_function_name = "custom_inbound_"..didinfo['call_type']
--	table.insert(xml, [[<action application="info" data=""/>]])
	Logger.debug("custom_function_name:::::::::::::::::::::::::" .. custom_function_name)
	_G[custom_function_name](xml,didinfo,userinfo,config,xml_did_rates,callerid_array,livecall_data) -- calls function from the global namespace
	return xml
end

function custom_inbound_0(xml,didinfo,userinfo,config,xml_did_rates,callerid_array,livecall_data)
	is_local_extension = "1"
        local bridge_str = ""
        local common_chan_var = ""
	local destination_str = {}
	string.gsub(didinfo['extensions'], "([^,|]+)", function(value) destination_str[#destination_str + 1] =     value;  end);
	table.insert(xml, [[<action application="set" data="calltype=DID-LOCAL"/>]]);  
	local deli_str = {}
	string.gsub(didinfo['extensions'], "([,|]+)", function(value) deli_str[#deli_str + 1] =     value;  end);           
		for i = 1, #destination_str do
			if notify then notify(xml,destination_str[i]) end
			local sip_codec = get_sip_codec(destination_str[i])
			local did_local_chan = ""
			if(sip_codec and sip_codec ~= "")then
				Logger.debug("[XML]  did_local_sip_codec : "..sip_codec)
				did_local_chan = ",absolute_codec_string=".."^^:"..sip_codec:gsub("%,", ":")
			end

			bridge_str = bridge_str.."[leg_timeout="..didinfo['leg_timeout']..did_local_chan.."]user/"..destination_str[i].."@${domain_name}"
			if i <= #deli_str then
				bridge_str = bridge_str..deli_str[i]
			end
		end
		table.insert(xml, [[<action application="bridge" data="]]..bridge_str..[["/>]]);
	
	-- To leave voicemail 
	leave_voicemail(xml,destination_number,destination_str[1])
	return xml;
end
function custom_inbound_1(xml,didinfo,userinfo,config,xml_did_rates,callerid_array,livecall_data)
	table.insert(xml, [[<action application="set" data="calltype=DID@IP"/>]]);
--	table.insert(xml, [[<action application="info" data=""/>]]);
	local did_local_chan = ""
	if(params:getHeader('variable_sip_from_user') and params:getHeader('variable_sip_from_user') ~= "")then
		local sip_codec = get_sip_codec(params:getHeader('variable_sip_from_user'))
		if(sip_codec and sip_codec ~= "")then
			Logger.debug("[XML] sip_codec : "..sip_codec) 
			did_local_chan = ",absolute_codec_string=".."^^:"..sip_codec:gsub("%,", ":")
		end
	end
	table.insert(xml, [[<action application="bridge" data="[leg_timeout=]]..didinfo['leg_timeout']..did_local_chan..[[]sofia/${sofia_profile_name}/]]..didinfo['extensions']..[["/>]]);
	return xml;
end
function custom_inbound_2(xml,didinfo,userinfo,config,xml_did_rates,callerid_array,livecall_data)
	table.insert(xml, [[<action application="set" data="calltype=DIRECT-IP"/>]]);
	local did_local_chan = ""
	if(params:getHeader('variable_sip_from_user') and params:getHeader('variable_sip_from_user') ~= "")then
		local sip_codec = get_sip_codec(params:getHeader('variable_sip_from_user'))
		if(sip_codec and sip_codec ~= "")then
			Logger.debug("[XML] sip_codec : "..sip_codec)
			did_local_chan = ",absolute_codec_string=".."^^:"..sip_codec:gsub("%,", ":")
		end
	end
	table.insert(xml, [[<action application="bridge" data="[leg_timeout=]]..didinfo['leg_timeout']..did_local_chan..[[]sofia/${sofia_profile_name}/]]..destination_number..[[@]]..didinfo['extensions']..[["/>]]);
	return xml;
end
function custom_inbound_3(xml,didinfo,userinfo,config,xml_did_rates,callerid_array,livecall_data)
	table.insert(xml, [[<action application="set" data="calltype=OTHER"/>]]); 
	table.insert(xml, [[<action application="bridge" data="]]..didinfo['extensions']..[["/>]]);
	return xml;
end
function custom_inbound_4(xml,didinfo,userinfo,config,xml_did_rates,callerid_array,livecall_data)
	table.insert(xml, [[<action application="set" data="calltype=Padrao"/>]]);     
	table.insert(xml, [[<action application="set" data="accountcode=]]..didinfo['account_code']..[["/>]]);
	table.insert(xml, [[<action application="set" data="caller_did_account_id=]]..userinfo['id']..[["/>]]);
	if(xml_did_rates ~= nil and xml_did_rates ~= '')then
	        table.insert(xml, [[<action application="set" data="origination_rates_did=]]..xml_did_rates..[["/>]]);
	end
	table.insert(xml, [[<action application="transfer" data="]]..didinfo['extensions']..[[ XML default"/>]]);
	return xml;
end
function custom_inbound_5(xml,didinfo,userinfo,config,xml_did_rates,callerid_array,livecall_data)
	is_local_extension = "1"
        local bridge_str = ""
	local destination_str = {}
	string.gsub(didinfo['extensions'], "([^,|]+)", function(value) destination_str[#destination_str + 1] =     value;  end);

        local common_chan_var = ""
	table.insert(xml, [[<action application="set" data="calltype=SIP-DID"/>]]); 
	local deli_str = {}
	string.gsub(didinfo['extensions'], "([,|]+)", function(value) deli_str[#deli_str + 1] =     value;  end);           
		common_chan_var = "{sip_contact_user="..destination_number.."}"
		for i = 1, #destination_str do
			if notify then notify(xml,destination_str[i]) end
			
			local sip_codec = get_sip_codec(destination_str[i])
			local did_local_chan = ""
			if(sip_codec and sip_codec ~= "")then
				Logger.debug("[XML]  did_local_sip_codec : "..sip_codec)
				did_local_chan = ",absolute_codec_string=".."^^:"..sip_codec:gsub("%,", ":")
			end		
			bridge_str = bridge_str.."[leg_timeout="..didinfo['leg_timeout']..did_local_chan.."]sofia/${sofia_profile_name}/"..destination_number.."${regex(${sofia_contact("..destination_str[i].."@${domain_name})}|^[^@]+(.*)|%1)}"
			if i <= #deli_str then
				bridge_str = bridge_str..deli_str[i]
			end
		end
		table.insert(xml, [[<action application="bridge" data="]]..common_chan_var..bridge_str..[["/>]]);            
        
-- To leave voicemail 
        leave_voicemail(xml,destination_number,destination_str[1])
	return xml;
end

-- Dialplan for sip2sip calls
function freeswitch_xml_local(xml,destination_number,destinationinfo,callerid_array,livecall_data)

    -------------- Caller Id translation ---------    
    callerid_array['cid_name'] = do_number_translation(destinationinfo['did_cid_translation'],callerid_array['cid_name'])
	callerid_array['cid_number'] = do_number_translation(destinationinfo['did_cid_translation'],callerid_array['cid_number'])
--	tr_localization = get_localization(destinationinfo['did_cid_translation'],'T')
	xml = freeswitch_xml_callerid(xml,callerid_array)	    	       
    ----------------------------------------------------------------------

    table.insert(xml, [[<action application="set" data="calltype=LOCAL"/>]]);
    table.insert(xml, [[<action application="set" data="receiver_accid=]]..destinationinfo['accountid']..[["/>]]);
	table.insert(xml, [[<action application="export" data="presence_data=]]..livecall_data..[[||||||Local"/>]]);
    if notify then notify(xml,destination_number) end


      table.insert(xml, [[<action application="bridge" data="[leg_timeout=]]..config['leg_timeout']..[[]user/]]..destination_number..[[@${domain_name}"/>]]);
    

    -- To leave voicemail 
    leave_voicemail(xml,destination_number,destination_number)

    return xml
end

-- Set callerid to override in calls
function freeswitch_xml_callerid(xml,calleridinfo)
        if (calleridinfo['cid_name'] ~= '' and calleridinfo['cid_name'] ~= '<null>')  then
                table.insert(xml, [[<action application="set" data="effective_caller_id_name=]]..calleridinfo['cid_name']..[["/>]]);
        end
        if (calleridinfo['cid_number'] ~= '' and calleridinfo['cid_number'] ~= '<null>')  then
                table.insert(xml, [[<action application="set" data="effective_caller_id_number=]]..calleridinfo['cid_number']..[["/>]]);
        end
        return xml
end

-- not found dialplan
function not_found(xml)

	table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
	table.insert(xml, [[<document type="freeswitch/xml">]]);
	table.insert(xml, [[<section name="result">]]);
	table.insert(xml, [[<result status="not found"/>]]);	    
	table.insert(xml, [[</section>]]);
	table.insert(xml, [[</document>]]);
	return xml
end

-- Generate header 
function xml_header(xml,destination_number)
    table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
	table.insert(xml, [[<document type="freeswitch/xml">]]);
	table.insert(xml, [[<section name="dialplan" description="FLUX Dialplan">]]);
	table.insert(xml, [[<context name="]]..params:getHeader("Caller-Context")..[[">]]);
	table.insert(xml, [[<extension name="]]..plus_destination_number(params:getHeader("Caller-Destination-Number"))..[[">]]); 
	table.insert(xml, [[<condition field="destination_number" expression="]]..plus_destination_number(params:getHeader("Caller-Destination-Number"))..[[">]]);
	return xml
end

-- To Leave voicemail dialplan 
function leave_voicemail(xml,vm_alternate_greet_id,vm_destination)
	table.insert(xml, [[<condition field="${cond(${user_data ]]..vm_destination..[[@${domain_name} param vm-enabled} == true ? YES : NO)}" expression="^YES$">]])
    table.insert(xml, [[<action application="answer"/>]]);    
	table.insert(xml, [[<action application="export" data="voicemail_alternate_greet_id=]]..vm_alternate_greet_id..[["/>]]);  
    table.insert(xml, [[<action application="voicemail" data="default $${domain_name} ]]..vm_destination..[["/>]]);    
    table.insert(xml, [[<anti-action application="hangup" data="${originate_disposition}"/>]])
    table.insert(xml, [[</condition>]])
end

-- Generate voicemail dialplan
function xml_voicemail(xml,destination_number)
	local xml = {}
	xml = xml_header(xml,destination_number)
	    table.insert(xml, [[<action application="answer"/>]]);      
	    table.insert(xml, [[<action application="voicemail" data="check default ${domain_name} ]]..params:getHeader("Hunt-Username")..[["/>]]);
	xml = xml_footer(xml)	   	    
	XML_STRING = table.concat(xml, "\n");
	Logger.debug("Generated XML:\n" .. XML_STRING)
	return xml
end

-- XML footer
function xml_footer(xml)
	table.insert(xml,[[</condition>]]);
	table.insert(xml,[[</extension>]]);
	table.insert(xml,[[</context>]]);
	table.insert(xml,[[</section>]]);
	table.insert(xml,[[</document>]]);
	return xml
end

-- Handle calls errors 
function error_xml_without_cdr(destination_number,error_code,calltype,playback_audio_notification,account_id)

     local xml = {};

	--Logger.debug("[ERROR]  call_direction:" .. call_direction)
	local log_type
	local log_message
	local hangup_cause 
	local audio_file
	local audio_file = ""
	local sound_path = "/usr/share/freeswitch/sounds/pt/BR/karina/"
	local accountcode = ""
	local post_cdrs = 0
	if(params:getHeader("variable_accountcode") == nil) then
		if(accountnumber ~= nil) then
			accountcode = accountnumber
		end
	else
		accountcode = params:getHeader("variable_accountcode");
	end
	if(error_code == "AUTHENTICATION_FAIL") then 
		log_type = "WARNING";
		log_message = "Accountcode ".. accountcode.." is not authenticated!!";
		hangup_cause = "AUTHENTICATION_FAIL";
		audio_file = sound_path ..  "flux_expired.wav";
	elseif(error_code == "ACCOUNT_INACTIVE_DELETED") then
		log_type = "WARNING";
		log_message = "Accountcode ".. accountcode.." is either inactive or deleted!!";
		hangup_cause = "ACCOUNT_INACTIVE_DELETED";
		audio_file = sound_path ..  "flux_expired.wav";
		if(account_id ~= '' and tonumber(account_id) >= 0)then post_cdrs = 1 end
	--~ Fraud Detection
	elseif(error_code == "FRAUD_CALL_PER_ACCOUNT") then
		log_type = "WARNING";
		log_message = "FRAUD_CALL_PER_ACCOUNT ".. account_id;
		hangup_cause = "FRAUD_CALL_PER_ACCOUNT";
	elseif(error_code == "FRAUD_COST_PER_ACCOUNT") then
		log_type = "WARNING";
		log_message = "FRAUD_COST_PER_ACCOUNT ".. account_id;
		hangup_cause = "FRAUD_COST_PER_ACCOUNT";
	elseif(error_code == "FRAUD_CALL_PER_DESTINATION") then
		log_type = "WARNING";
		log_message = "FRAUD_CALL_PER_DESTINATION ".. account_id;
		hangup_cause = "FRAUD_CALL_PER_DESTINATION";
	elseif(error_code == "FRAUD_COST_PER_DESTINATION") then
		log_type = "WARNING";
		log_message = "FRAUD_COST_PER_DESTINATION ".. account_id;
		hangup_cause = "FRAUD_COST_PER_DESTINATION";
	--~ Fraud Detection End
	--~ CLI - NONCLI Rategroup
	elseif(error_code == "CALLERID_NOT_FOUND") then
		log_type = "WARNING";
		log_message = "CALLERID_NOT_FOUND ".. account_id;
		hangup_cause = "CALLERID_NOT_FOUND";
	--~ CLI - NONCLI Rategroup End
	elseif(error_code == "ACCOUNT_EXPIRE") then
		log_type = "WARNING";
		log_message = "Accountcode ".. accountcode.." Account has been expired!!";
		hangup_cause = "ACCOUNT_EXPIRE";
		audio_file = sound_path ..  "flux_expired.wav";
	elseif(error_code == "NO_SUFFICIENT_FUND") then
		log_type = "WARNING";
		log_message = "Accountcode ".. accountcode.." doesn't have sufficiant fund!!";
		hangup_cause = "NO_SUFFICIENT_FUND";
		audio_file = sound_path ..  "flux-not-enough-credit.wav";
	elseif(error_code == "DESTINATION_BLOCKED") then
		log_type = "WARNING";
		log_message = "Accountcode ".. accountcode..". Dialed number ("..destination_number..") is blocked for account!!";
		hangup_cause = "DESTINATION_BLOCKED";
		audio_file = sound_path ..  "flux-badnumber.wav";
	elseif(error_code == "ORIGINATION_RATE_NOT_FOUND") then
		log_type = "WARNING";

		log_message = "Accountcode ".. accountcode ..". Dialed number ("..destination_number..")  origination rates not found!!";
		hangup_cause = "ORIGINATION_RATE_NOT_FOUND";
		audio_file = sound_path ..  "flux-badphone.wav";
	elseif(error_code == "DID_RATE_NOT_FOUND") then
	log_type = "WARNING";
	log_message = "Accountcode ".. accountcode ..". Dialed number ("..destination_number..")  did rates not found!!";
	hangup_cause = "DID_RATE_NOT_FOUND";
	audio_file = sound_path ..  "flux-badphone.wav";
	elseif(error_code == "RESELLER_COST_CHEAP") then
		log_type = "WARNING";
		log_message = "Accountcode ".. accountcode..". Dialed number ("..destination_number..") , Reseller call is priced too cheap! Call being barred!!";
		hangup_cause = "RESELLER_COST_CHEAP";
		audio_file = sound_path ..  "flux-badphone.wav";

	elseif(error_code == "TERMINATION_RATE_NOT_FOUND") then
		log_type = "WARNING";
		log_message = "Accountcode ".. accountcode..". Dialed number ("..destination_number..") termination rates not found!!";
		hangup_cause = "TERMINATION_RATE_NOT_FOUND";
		audio_file = sound_path ..  "flux-badphone.wav";
	elseif(error_code == "DID_DESTINATION_NOT_FOUND") then
		log_type = "WARNING";
		log_message = "Accountcode ".. accountcode..". Dialed number ("..destination_number..") destination not found!!";
		hangup_cause = "DID_DESTINATION_NOT_FOUND";
		audio_file = sound_path ..  "flux-badphone.wav";
	elseif(error_code == "UNALLOCATED_NUMBER") then
		log_type = "WARNING";
		log_message = "Accountcode ".. accountcode..". Dialed DID number ("..destination_number..") is inactive!!";
		hangup_cause = "UNALLOCATED_NUMBER";
		audio_file = sound_path ..  "flux-badphone.wav";
	elseif(error_code == "NO_ROUTE_DESTINATION") then
		log_type = "WARNING";
		log_message = "Accountcode ".. accountcode..". Dialed DID number ("..destination_number..") routes not set!!";
		hangup_cause = "NO_ROUTE_DESTINATION";
		audio_file = sound_path ..  "flux-badphone.wav";
	end
	    Logger.debug("post_cdrs:::" .. post_cdrs)
	if(calltype ~= "FLUX-CALLINGCARD" and tonumber(post_cdrs) ==0) then
	    xml = xml_header(xml,destination_number)
    	table.insert(xml, [[<action application="log" data="]]..log_type.." "..log_message..[["/>]]); 

	    if (playback_audio_notification == "0") then
	    	table.insert(xml, [[<action application="playback" data="]]..audio_file..[["/>]]);
	    end


	    local callstart = os.date("!%Y-%m-%d %H:%M:%S")
		
	    if (callerid_array['original_cid_name'] ~= '' and callerid_array['original_cid_name'] ~= '<null>')  then
               table.insert(xml, [[<action application="set" data="original_caller_id_name=]]..callerid_array['original_cid_name']..[["/>]]);
            end
            if (callerid_array['cid_number'] ~= '' and callerid_array['cid_number'] ~= '<null>')  then
               table.insert(xml, [[<action application="set" data="original_caller_id_number=]]..callerid_array['original_cid_name']..[["/>]]);
            end
		
            table.insert(xml, [[<action application="set" data="error_cdr=1"/>]]);
	    table.insert(xml, [[<action application="set" data="callstart=]]..callstart..[["/>]]);
	    table.insert(xml, [[<action application="set" data="account_id=]]..account_id..[["/>]]);
	    local parent_id= ""
	    if(account_id ~= '' and tonumber(account_id) >= 0)then
	        parent_id=get_parentid(account_id);
	    end
	    if(parent_id~=nil and parent_id~="") then
			table.insert(xml, [[<action application="set" data="parent_id=]]..parent_id..[["/>]]);
	    end
	    if(call_direction == 'local')then
		    table.insert(xml, [[<action application="set" data="call_direction=inbound"/>]]);
	    else
		    table.insert(xml, [[<action application="set" data="call_direction=]]..call_direction..[["/>]]);
	    end	
	    if(call_direction == 'inbound')then
		    table.insert(xml, [[<action application="set" data="calltype=DID"/>]]);
			Logger.debug("Calltype::" .. calltype)
			if (calltype == nil or calltype == 'Padrao')then
				table.insert(xml, [[<action application="set" data="calltype=DID"/>]]);
			else
		    	table.insert(xml, [[<action application="set" data="calltype=]]..calltype..[["/>]]);
			end
	    end
	    if(call_direction == 'local')then
		    table.insert(xml, [[<action application="set" data="calltype=LOCAL"/>]]);
	    end
		if (params:getHeader("variable_sip_h_P-Voice_broadcast") == 'true') then
			table.insert(xml, [[<action application="set" data="calltype=BROADCAST"/>]]);
		end
	    table.insert(xml, [[<action application="set" data="sip_ignore_remote_cause=true"/>]]);        
	    table.insert(xml, [[<action application="set" data="call_processed=internal"/>]]);
	    table.insert(xml, [[<action application="set" data="effective_destination_number=]]..destination_number..[["/>]]); 
	    --table.insert(xml, [[<action application="set" data="hangup_cause=${last_bridge_hangup_cause}"/>]]);  
	    --table.insert(xml, [[<action application="set" data="process_cdr=false"/>]]);      
	    table.insert(xml, [[<action application="set" data="last_bridge_hangup_cause=]]..hangup_cause..[["/>]]);        
	    table.insert(xml, [[<action application="hangup" data="]]..hangup_cause..[["/>]]);      

	    xml = xml_footer(xml);
	    XML_STRING = table.concat(xml, "\n");
	    Logger.debug("Generated XML:\n" .. XML_STRING)
	    return
	elseif(tonumber(post_cdrs) ==1)then
	    Logger.debug("post_cdrs IN:::" .. post_cdrs)
		xml = xml_header(xml,destination_number)
	  	table.insert(xml, [[<action application="set" data="sip_ignore_remote_cause=true"/>]]);        
	    table.insert(xml, [[<action application="playback" data="]]..audio_file..[["/>]]);
		table.insert(xml, [[<action application="hangup" data="]]..hangup_cause..[["/>]]);  
		xml = xml_footer(xml);
		XML_STRING = table.concat(xml, "\n");
		Logger.debug("Generated XML:\n" .. XML_STRING)
		return
    
	else
		session:execute("set", "process_cdr=false" );
		session:streamFile( audio_file );
	end
end

-- Generate calling card dialplan
function generate_cc_dialplan(destination_number)
	local xml = {};
	table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
	table.insert(xml, [[<document type="freeswitch/xml">]]);
		table.insert(xml, [[<section name="dialplan" description="FLUX Dialplan">]]);
			table.insert(xml, [[<context name="]]..params:getHeader("Caller-Context")..[[">]]);
				table.insert(xml, [[<extension name="]]..destination_number..[[">]]); 
				table.insert(xml, [[<condition field="destination_number" expression="]]..plus_destination_number(destination_number)..[[">]]);
					table.insert(xml, [[<action application="log" data="INFO FLUX - Calling Card Call"/>]]);        
					table.insert(xml, [[<action application="answer"/>]]);
					table.insert(xml, [[<action application="sleep" data="2000"/>]]);                    
					table.insert(xml, [[<action application="lua" data="flux-callingcards.lua"/>]]);    
				table.insert(xml,[[</condition>]]);
				table.insert(xml,[[</extension>]]);
			table.insert(xml,[[</context>]]);
		table.insert(xml,[[</section>]]);
	table.insert(xml,[[</document>]]);
	XML_STRING = table.concat(xml, "\n");
	Logger.debug("Generated XML:\n" .. XML_STRING)
end

function generate_cc_facilities(destination_number)
	local xml = {};
	if string.find(destination_number,"^%*7[0-3]") then
	table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
	table.insert(xml, [[<document type="freeswitch/xml">]]);
	table.insert(xml, [[<section name="dialplan" description="FLUX SBC Dialplan">]]);
	table.insert(xml, [[<context name="]]..params:getHeader("Caller-Context")..[[">]]);
 table.insert(xml, [[<extension name="programacao">]]);
      table.insert(xml, [[<condition regex="any">]]);
        table.insert(xml, [[<regex field="destination_number" expression="^\*(1[1-4])"/>]]);
        table.insert(xml, [[<regex field="destination_number" expression="^\*(2[45])"/>]]);
        table.insert(xml, [[<regex field="destination_number" expression="^\*(7[0-3])"/>]]);
        table.insert(xml, [[<regex field="destination_number" expression="^\*(9[0-5])$"/>]]);
         table.insert(xml, [[<action application="lua" data="flux/lib/pbx_scripts/facilities.lua"/>]]);
      table.insert(xml, [[</condition>]]);
    table.insert(xml, [[</extension>]]);
    table.insert(xml,[[</context>]]);
		table.insert(xml,[[</section>]]);
	table.insert(xml,[[</document>]]);
	XML_STRING = table.concat(xml, "\n");
	Logger.debug("Generated XML:\n" .. XML_STRING)

elseif string.find(destination_number,"^%*9[0-3]") then
    	table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
	table.insert(xml, [[<document type="freeswitch/xml">]]);
	table.insert(xml, [[<section name="dialplan" description="FLUX SBC Dialplan">]]);
	table.insert(xml, [[<context name="]]..params:getHeader("Caller-Context")..[[">]]);
 table.insert(xml, [[<extension name="programacao">]]);
      table.insert(xml, [[<condition regex="any">]]);
        table.insert(xml, [[<regex field="destination_number" expression="^\*(1[1-4])"/>]]);
        table.insert(xml, [[<regex field="destination_number" expression="^\*(2[45])"/>]]);
        table.insert(xml, [[<regex field="destination_number" expression="^\*(7[0-3])"/>]]);
        table.insert(xml, [[<regex field="destination_number" expression="^\*(9[0-5])$"/>]]);
         table.insert(xml, [[<action application="lua" data="flux/scripts/flux/lib/pbx_scripts/facilities.lua"/>]]);
      table.insert(xml, [[</condition>]]);
    table.insert(xml, [[</extension>]]);
    table.insert(xml,[[</context>]]);
		table.insert(xml,[[</section>]]);
	table.insert(xml,[[</document>]]);
	XML_STRING = table.concat(xml, "\n");
	Logger.debug("Generated XML:\n" .. XML_STRING)

elseif string.find(destination_number,"^%*7") then
    	table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
	table.insert(xml, [[<document type="freeswitch/xml">]]);
	table.insert(xml, [[<section name="dialplan" description="FLUX SBC Dialplan">]]);
	table.insert(xml, [[<context name="]]..params:getHeader("Caller-Context")..[[">]]);

	   table.insert(xml, [[<extension name="captura">]]);
      table.insert(xml, [[<condition field="destination_number" expression="^\*7$|\*8$|\*9\d">]]);
	table.insert(xml, [[<action application="lua" data="flux/scripts/flux/lib/pbx_scripts/callpickup.lua"/>]]);
	table.insert(xml, [[<action application="intercept" data="${intercept_uuid}"/>]]);
	table.insert(xml, [[<action application="sleep" data="2000"/>]]);
    table.insert(xml, [[</condition>]]);
    table.insert(xml, [[</extension>]]);
    table.insert(xml,[[</context>]]);
		table.insert(xml,[[</section>]]);
	table.insert(xml,[[</document>]]);
	XML_STRING = table.concat(xml, "\n");
	Logger.debug("Generated XML:\n" .. XML_STRING)

else

	table.insert(xml, [[<?xml version="1.0" encoding="UTF-8" standalone="no"?>]]);
	table.insert(xml, [[<document type="freeswitch/xml">]]);
		table.insert(xml, [[<section name="dialplan" description="FLUX SBC Dialplan">]]);
			table.insert(xml, [[<context name="]]..params:getHeader("Caller-Context")..[[">]]);
				table.insert(xml, [[<extension name="]]..destination_number..[[">]]);
				table.insert(xml, [[<condition field="destination_number" expression="]]..plus_destination_number(destination_number)..[[">]]);
					table.insert(xml, [[<action application="log" data="INFO FLUX SBC - IVR Call"/>]]);
					table.insert(xml, [[<action application="answer"/>]]);
					table.insert(xml, [[<action application="sleep" data="2000"/>]]);
					table.insert(xml, [[<action application="set" data="ivr_menu_uuid=]]..ivr_uuid..[["/>]]);
					table.insert(xml, [[<action application="set" data="domain_name=$${domain}"/>]]);
					table.insert(xml, [[<action application="set" data="calltype=LOCAL"/>]]);
					table.insert(xml, [[<action application="lua" data="ivr_menu.lua"/>]]);
				table.insert(xml,[[</condition>]]);
				table.insert(xml,[[</extension>]]);
			table.insert(xml,[[</context>]]);
		table.insert(xml,[[</section>]]);
	table.insert(xml,[[</document>]]);
	XML_STRING = table.concat(xml, "\n");
	Logger.debug("Generated XML:\n" .. XML_STRING)
end
end

-- Set reseller concurrent call limits
function set_cc_limit_resellers(reseller_userinfo)

	local xml_temp = ""
	-- Set CPS limit for reseller if > 0
	if (tonumber(reseller_userinfo['maxchannels']) > 0) then
		xml_temp = "<action application=\"limit\" data=\"db "..reseller_userinfo['number'].. " user_"..reseller_userinfo['number'].." "..reseller_userinfo['maxchannels'].." !SWITCH_CONGESTION\"/>"
	end

	if (tonumber(reseller_userinfo['cps']) > 0) then
		xml_temp = xml_temp.."<action application=\"limit\" data=\"hash CPS_"..reseller_userinfo['number'].. " CPS_user_"..reseller_userinfo['number'].." "..reseller_userinfo['cps'].."/1 !SWITCH_CONGESTION\"/>"
	end

	return xml_temp
end
