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

function split(str, pat)
   local t = {}  -- NOTE: use {n = 0} in Lua-5.0
   local fpat = "(.-)" .. pat
   local last_end = 1
   local s, e, cap = str:find(fpat, 1)
   while s do
      if s ~= 1 or cap ~= "" then
	 table.insert(t,cap)
      end
      last_end = e+1
      s, e, cap = str:find(fpat, last_end)
   end
   if last_end <= #str then
      cap = str:sub(last_end)
      table.insert(t, cap)
   end
   return t
end 

function explode2(div,str)
    if (div=='') then return false end
    local pos,arr = 0,{}
    for st,sp in function() return string.find(str,div,pos,true) end do
        table.insert(arr,string.sub(str,pos,st-1))
        pos = sp + 1
    end
    table.insert(arr,string.sub(str,pos))
    return arr
end

function trim(s)
		if (s) then
			return s:gsub("^%s+", ""):gsub("%s+$", "")
		end
	end
	
function explode ( seperator, str )
		local pos, arr = 0, {}
		if (seperator ~= nil and str ~= nil) then
			for st, sp in function() return string.find( str, seperator, pos, true ) end do -- for each divider found
				table.insert( arr, string.sub( str, pos, st-1 ) ) -- attach chars left of current divider
				pos = sp + 1 -- jump past current divider
			end
			table.insert( arr, string.sub( str, pos ) ) -- attach chars right of last divider
		end
		return arr
	end