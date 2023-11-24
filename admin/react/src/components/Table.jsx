import React from 'react'
import TableRow from './TableRow'
import PropTypes from 'prop-types'

function Table({
  tableList,
  selectedIds,
  onSelectAll,
  onSelect,
  setIsPopupOpen,
  setOriginalJson,
  setModifiedJson
}) {
  const isAllSelected =
    selectedIds.length > 0 &&
    selectedIds.length ===
      tableList.filter(response => response.data.is_available).length

  return (
    <table>
      <thead>
        <tr>
          <th>
            <input
              type="checkbox"
              checked={isAllSelected}
              onChange={onSelectAll}
            />
          </th>
          <th className="text-center">ID</th>
          <th className="text-center"></th>
          <th className="text-center">Name</th>
          <th className="text-center">Profile URL</th>
          <th className="text-center">Current Status</th>
          <th className="text-center">Availability</th>
          <th className="text-center"></th>
        </tr>
      </thead>
      <tbody>
        {tableList.map(response => (
          <TableRow
            key={response.id}
            response={response}
            isSelected={selectedIds.includes(response.id)}
            onSelect={onSelect}
            setIsPopupOpen={setIsPopupOpen}
            setOriginalJson={setOriginalJson}
            setModifiedJson={setModifiedJson}
          />
        ))}
      </tbody>
    </table>
  )
}

Table.propTypes = {
  tableList: PropTypes.arrayOf(PropTypes.object).isRequired,
  selectedIds: PropTypes.arrayOf(PropTypes.number).isRequired,
  onSelectAll: PropTypes.func.isRequired,
  onSelect: PropTypes.func.isRequired,
  setIsPopupOpen: PropTypes.func.isRequired,
  setOriginalJson: PropTypes.func.isRequired,
  setModifiedJson: PropTypes.func.isRequired
}

export default Table
