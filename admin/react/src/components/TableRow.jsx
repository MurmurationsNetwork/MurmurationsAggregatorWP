import React from 'react'
import PropTypes from 'prop-types'
import { getCustomNodes } from '../utils/api'

function TableRow({
  response,
  isSelected,
  onSelect,
  setIsPopupOpen,
  setOriginalJson,
  setModifiedJson
}) {
  const handleSeeUpdates = async (mapId, profileUrl, profileData) => {
    const response = await getCustomNodes(mapId, profileUrl)
    const responseData = await response.json()
    if (!response.ok) {
      alert(
        `Error fetching original profile with response: ${
          response.status
        } ${JSON.stringify(responseData)}`
      )
      return
    }

    setOriginalJson(responseData[0].data)
    setModifiedJson(profileData)
    setIsPopupOpen(true)
  }

  return (
    <tr>
      <td>
        <input
          type="checkbox"
          checked={isSelected}
          onChange={() => onSelect(response.id)}
          disabled={response.extra_notes === 'unavailable'}
        />
      </td>
      <td className="text-center">{response.id}</td>
      <td className="text-center">{response.name}</td>
      <td className="text-center">{response.profile_url}</td>
      <td className="text-center">{response.status}</td>
      <td className="text-center">
        {response.extra_notes === 'see updates' ? (
          <button
            onClick={() =>
              handleSeeUpdates(
                response.map_id,
                response.profile_url,
                response.profile_data
              )
            }
          >
            See Updates
          </button>
        ) : (
          response.extra_notes
        )}
      </td>
    </tr>
  )
}

TableRow.propTypes = {
  response: PropTypes.object.isRequired,
  isSelected: PropTypes.bool.isRequired,
  onSelect: PropTypes.func.isRequired,
  setIsPopupOpen: PropTypes.func.isRequired,
  setOriginalJson: PropTypes.func.isRequired,
  setModifiedJson: PropTypes.func.isRequired
}

export default TableRow
