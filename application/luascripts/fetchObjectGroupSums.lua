--
-- fetchObjectGroupSums.lua
--
-- LUA helper script to fetch group memberships for all objects of a specific
-- type
--

local objectType = KEYS[1]
local objectsKey = 'icinga:config:' .. objectType
local cursor = KEYS[2]

local result = {}
local fetchAll = false
local limit = 2500
local done = false
local nextkey
local payload
local groups

repeat
    local res = redis.call('HSCAN', objectsKey, cursor, 'COUNT', limit)
    cursor = res[1]
    local payload = res[2]

    for i, v in ipairs(payload) do
        if i % 2 == 1 then
            nextkey = redis.sha1hex(v)
        else
            groups = cjson.decode(v)['groups']
            if next(groups) == nil then
                -- no entry & result for empty groups
            else
                local rawGroups = {}
                for x, y in ipairs(groups) do
                    rawGroups[x] = redis.sha1hex(y)
                end
                result[nextkey] = rawGroups
            end
        end
    end

    if not fetchAll or cursor == '0' then
        done = true
    end
until done

return {cursor, cjson.encode(result)}
